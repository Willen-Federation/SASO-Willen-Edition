<?php
namespace saso\authExt;

use Saso\Application\Auth\AdminGuard;
use saso\framework\Setter;
use saso\framework\View;
use saso\repository\DBConnection;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;

final class ProviderView implements View
{
    use Setter;
    private \Closure $content;
    public bool $authorized = false;
    public string $mode = 'new';
    public array $provider = [
        'id' => 0,
        'name' => '',
        'type' => 'oidc',
        'enabled' => 1,
        'is_default' => 0,
        'issuer_or_metadata_url' => '',
        'client_id' => '',
        'scopes' => '',
        'claim_mapping' => '{}',
    ];
    public string $message = '';
    public string $title = '';
    public bool $hasSecret = false;

    /** Computed callback URLs for display */
    public string $callbackUrl = '';
    public string $acsUrl = '';
    public string $slsUrl = '';

    public function __construct(private array $query, private array $post)
    {
        $pdo = DBConnection::getPdo();
        $this->authorized = (new AdminGuard($pdo))->isAdmin(
            isset($_SESSION['id']) && is_string($_SESSION['id']) ? $_SESSION['id'] : null,
        );

        if (!$this->authorized) {
            return;
        }

        // Determine mode: edit or new
        if (isset($this->query['edit']) && is_numeric($this->query['edit'])) {
            $this->mode = 'edit';
            $stmt = $pdo->prepare('SELECT * FROM auth_provider WHERE id = :id');
            $stmt->bindValue(':id', (int)$this->query['edit']);
            $stmt->execute();
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                $this->provider = $row;
                $this->hasSecret = !empty($row['client_secret_encrypted']);
            }
            $this->computeUrls((int)$this->provider['id']);
        }

        // Handle Delete Action
        if (isset($this->query['delete']) && is_numeric($this->query['delete'])) {
            $stmt = $pdo->prepare('DELETE FROM auth_provider WHERE id = :id');
            $stmt->bindValue(':id', (int)$this->query['delete']);
            $stmt->execute();
            header('Location: ./auth/providers/');
            exit;
        }

        // Handle Form Submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = (string)($this->post['name'] ?? '');
            $type = (string)($this->post['type'] ?? 'oidc');
            $enabled = isset($this->post['enabled']) ? 1 : 0;
            $is_default = isset($this->post['is_default']) ? 1 : 0;
            $issuer = (string)($this->post['issuer_or_metadata_url'] ?? '');
            $client_id = (string)($this->post['client_id'] ?? '');
            $scopes = (string)($this->post['scopes'] ?? '');
            $client_secret_raw = (string)($this->post['client_secret'] ?? '');

            // Build claim_mapping from structured fields
            $claim_mapping = $this->buildClaimMapping($type);

            // Auth0: auto-derive issuer URL from domain when the field was left blank.
            if ($type === 'oidc' && ($this->post['flavor'] ?? '') === 'auth0' && $issuer === '') {
                $domain = trim((string)($this->post['auth0_domain'] ?? ''));
                if ($domain !== '') {
                    $issuer = 'https://' . $domain . '/.well-known/openid-configuration';
                }
            }

            if ($name === '') {
                $this->message = 'Name is required.';
            } else {
                if ($is_default) {
                    $pdo->exec('UPDATE auth_provider SET is_default = 0');
                }

                // Encrypt client secret if provided
                $secretCipher = null;
                if ($client_secret_raw !== '') {
                    $encryptor = $this->getEncryptor();
                    if ($encryptor !== null) {
                        $secretCipher = $encryptor->encrypt($client_secret_raw);
                    }
                }

                $nowStr = date('Y-m-d H:i:s');
                if ($this->mode === 'edit') {
                    $sql = 'UPDATE auth_provider SET name = :name, type = :type, enabled = :enabled, is_default = :is_default, issuer_or_metadata_url = :issuer, client_id = :client_id, scopes = :scopes, claim_mapping = :claim_mapping, updated_at = :updated_at';
                    if ($secretCipher !== null) {
                        $sql .= ', client_secret_encrypted = :secret';
                    }
                    $sql .= ' WHERE id = :id';
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':id', (int)$this->provider['id']);
                    if ($secretCipher !== null) {
                        $stmt->bindValue(':secret', $secretCipher, \PDO::PARAM_LOB);
                    }
                } else {
                    $stmt = $pdo->prepare('INSERT INTO auth_provider (name, type, enabled, is_default, issuer_or_metadata_url, client_id, client_secret_encrypted, scopes, claim_mapping, created_at, updated_at) VALUES (:name, :type, :enabled, :is_default, :issuer, :client_id, :secret, :scopes, :claim_mapping, :created_at, :updated_at)');
                    $stmt->bindValue(':created_at', $nowStr);
                    $stmt->bindValue(':secret', $secretCipher, $secretCipher === null ? \PDO::PARAM_NULL : \PDO::PARAM_LOB);
                }
                $stmt->bindValue(':updated_at', $nowStr);
                $stmt->bindValue(':name', $name);
                $stmt->bindValue(':type', $type);
                $stmt->bindValue(':enabled', $enabled);
                $stmt->bindValue(':is_default', $is_default);
                $stmt->bindValue(':issuer', $issuer !== '' ? $issuer : null);
                $stmt->bindValue(':client_id', $client_id !== '' ? $client_id : null);
                $stmt->bindValue(':scopes', $scopes !== '' ? $scopes : null);
                $stmt->bindValue(':claim_mapping', $claim_mapping);
                $stmt->execute();

                header('Location: ./auth/providers/');
                exit;
            }
        }
    }

    /**
     * Build the claim_mapping JSON from structured POST fields.
     * Merges _config extras (flavor, SAML certs, etc.) with user claim overrides.
     */
    private function buildClaimMapping(string $type): string
    {
        $config = [];

        if ($type === 'oidc') {
            $flavor = (string)($this->post['flavor'] ?? 'oidc');
            $config['flavor'] = $flavor;

            if ($flavor === 'auth0') {
                $domain = trim((string)($this->post['auth0_domain'] ?? ''));
                if ($domain !== '') {
                    $config['domain'] = $domain;
                }
                $audience = trim((string)($this->post['auth0_audience'] ?? ''));
                if ($audience !== '') {
                    $config['audience'] = $audience;
                }
            }
        } elseif ($type === 'saml') {
            $config['flavor'] = 'saml';
            $entityId = (string)($this->post['entity_id'] ?? '');
            if ($entityId !== '') {
                $config['entity_id'] = $entityId;
            }
            $nameidFormat = (string)($this->post['nameid_format'] ?? '');
            if ($nameidFormat !== '') {
                $config['nameid_format'] = $nameidFormat;
            }
            $idpCert = (string)($this->post['idp_x509_cert'] ?? '');
            if ($idpCert !== '') {
                $config['idp_x509_cert'] = $idpCert;
            }
            $spCert = (string)($this->post['sp_x509_cert'] ?? '');
            if ($spCert !== '') {
                $config['sp_x509_cert'] = $spCert;
            }
            $spKey = (string)($this->post['sp_private_key'] ?? '');
            if ($spKey !== '') {
                $config['sp_private_key'] = $spKey;
            }
        }

        // Preserve raw claim overrides from textarea
        $rawJson = (string)($this->post['claim_mapping_raw'] ?? '{}');
        $decoded = json_decode($rawJson, true);
        if (!is_array($decoded)) {
            $decoded = [];
        }
        // Remove _config from user input — we build it ourselves
        unset($decoded['_config']);

        $decoded['_config'] = $config;

        return (string) json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function computeUrls(int $id): void
    {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = rtrim($proto . '://' . $host, '/');
        $this->callbackUrl = $base . '/auth/callback/' . $id;
        $this->acsUrl = $base . '/auth/saml/acs/' . $id;
        $this->slsUrl = $base . '/auth/saml/sls/' . $id;
    }

    private function getEncryptor(): ?SecretEncryptor
    {
        $appKey = (string)(getenv('APP_KEY') ?: '');
        if ($appKey === '') {
            return null;
        }
        $decoded = base64_decode($appKey, true);
        if ($decoded === false || strlen($decoded) !== 32) {
            return null;
        }
        return new SecretEncryptor($decoded);
    }

    public function display(): void
    {
        require_once 'authExt/template/provider_form.php';
    }

    public function onRoot(): bool
    {
        return true;
    }

    public function getTitle(): string
    {
        return $this->mode === 'edit' ? 'Edit Auth Provider' : 'Add Auth Provider';
    }

    public function getContent(): \Closure
    {
        return $this->content;
    }
}
