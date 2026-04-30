<?php
namespace saso\authExt;

use Saso\Application\Auth\AdminGuard;
use saso\framework\Setter;
use saso\framework\View;
use saso\repository\DBConnection;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;

/**
 * Handles three modes driven by URL segments:
 *   /auth/provider/           → mode=select  (type picker)
 *   /auth/provider/new/{flavor} → mode=new  (add form for that flavor)
 *   /auth/provider/edit/{id}  → mode=edit   (edit existing)
 *   /auth/provider/delete/{id}→ redirect after delete
 */
final class ProviderView implements View
{
    use Setter;
    private \Closure $content;

    public bool $authorized = false;
    /** 'select' | 'new' | 'edit' */
    public string $mode = 'select';
    public string $flavor = 'auth0';
    public array $provider = [
        'id' => 0, 'name' => '', 'type' => 'oidc',
        'enabled' => 1, 'is_default' => 0,
        'issuer_or_metadata_url' => '', 'client_id' => '', 'scopes' => '',
        'claim_mapping' => '{}',
    ];
    public string $message = '';
    public bool $hasSecret = false;

    /** Computed endpoint URLs (filled in edit mode or after POST error) */
    public string $baseUrl    = '';
    public string $callbackUrl = '';
    public string $acsUrl      = '';
    public string $slsUrl      = '';

    /** _config extras parsed for the template */
    public array $cfg = [];

    public function __construct(private array $query, private array $post)
    {
        $pdo = DBConnection::getPdo();
        $this->authorized = (new AdminGuard($pdo))->isAdmin(
            isset($_SESSION['id']) && is_string($_SESSION['id']) ? $_SESSION['id'] : null,
        );
        if (!$this->authorized) {
            return;
        }

        $this->baseUrl = $this->computeBaseUrl();

        // ── Delete ─────────────────────────────────────────────────────────
        if (isset($this->query['delete']) && is_numeric($this->query['delete'])) {
            $stmt = $pdo->prepare('DELETE FROM auth_provider WHERE id = :id');
            $stmt->bindValue(':id', (int) $this->query['delete']);
            $stmt->execute();
            header('Location: ./auth/providers/');
            exit;
        }

        // ── Edit ───────────────────────────────────────────────────────────
        if (isset($this->query['edit']) && is_numeric($this->query['edit'])) {
            $this->mode = 'edit';
            $stmt = $pdo->prepare('SELECT * FROM auth_provider WHERE id = :id');
            $stmt->bindValue(':id', (int) $this->query['edit']);
            $stmt->execute();
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                $this->provider = $row;
                $this->hasSecret = !empty($row['client_secret_encrypted']);
            }
            $this->flavor = $this->extractFlavor($this->provider);
            $this->cfg    = $this->extractConfig($this->provider);
            $this->computeUrls((int) $this->provider['id']);

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->handlePost($pdo);
            }
            return;
        }

        // ── New ────────────────────────────────────────────────────────────
        // query['new'] = 'auth0'|'cognito'|'saml'|'oidc' means flavor chosen.
        // query = [] means step-1 selector.
        if (isset($this->query['new'])) {
            $this->mode   = 'new';
            $this->flavor = $this->query['new'];

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->handlePost($pdo);
            }
            return;
        }

        // No query segments → step-1 type selector
        $this->mode = 'select';
    }

    // ── POST handler ────────────────────────────────────────────────────────

    private function handlePost(\PDO $pdo): void
    {
        $name       = (string) ($this->post['name'] ?? '');
        $type       = $this->mapFlavorToType($this->flavor);
        $enabled    = isset($this->post['enabled'])    ? 1 : 0;
        $isDefault  = isset($this->post['is_default']) ? 1 : 0;
        $scopes     = (string) ($this->post['scopes'] ?? '');
        $clientId   = (string) ($this->post['client_id'] ?? '');
        $secretRaw  = (string) ($this->post['client_secret'] ?? '');

        // Build issuer URL (flavor-aware)
        $issuer = trim((string) ($this->post['issuer_or_metadata_url'] ?? ''));
        if ($issuer === '') {
            $issuer = $this->deriveIssuer();
        }

        $claimMapping = $this->buildClaimMapping();

        if ($name === '') {
            $this->message = 'Name is required.';
            return;
        }

        if ($isDefault) {
            $pdo->exec('UPDATE auth_provider SET is_default = 0');
        }

        $secretCipher = null;
        if ($secretRaw !== '') {
            $enc = $this->getEncryptor();
            if ($enc !== null) {
                $secretCipher = $enc->encrypt($secretRaw);
            }
        }

        $nowStr = date('Y-m-d H:i:s');

        if ($this->mode === 'edit') {
            $sql = 'UPDATE auth_provider SET name=:name,type=:type,enabled=:enabled,is_default=:is_default,issuer_or_metadata_url=:issuer,client_id=:client_id,scopes=:scopes,claim_mapping=:claim_mapping,updated_at=:updated_at';
            if ($secretCipher !== null) {
                $sql .= ',client_secret_encrypted=:secret';
            }
            $sql .= ' WHERE id=:id';
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id', (int) $this->provider['id']);
            if ($secretCipher !== null) {
                $stmt->bindValue(':secret', $secretCipher, \PDO::PARAM_LOB);
            }
        } else {
            $stmt = $pdo->prepare('INSERT INTO auth_provider (name,type,enabled,is_default,issuer_or_metadata_url,client_id,client_secret_encrypted,scopes,claim_mapping,created_at,updated_at) VALUES (:name,:type,:enabled,:is_default,:issuer,:client_id,:secret,:scopes,:claim_mapping,:created_at,:updated_at)');
            $stmt->bindValue(':created_at', $nowStr);
            $stmt->bindValue(':secret', $secretCipher, $secretCipher === null ? \PDO::PARAM_NULL : \PDO::PARAM_LOB);
        }

        $stmt->bindValue(':name',         $name);
        $stmt->bindValue(':type',         $type);
        $stmt->bindValue(':enabled',      $enabled);
        $stmt->bindValue(':is_default',   $isDefault);
        $stmt->bindValue(':issuer',       $issuer !== '' ? $issuer : null);
        $stmt->bindValue(':client_id',    $clientId !== '' ? $clientId : null);
        $stmt->bindValue(':scopes',       $scopes !== '' ? $scopes : null);
        $stmt->bindValue(':claim_mapping', $claimMapping);
        $stmt->bindValue(':updated_at',   $nowStr);
        $stmt->execute();

        header('Location: ./auth/providers/');
        exit;
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function buildClaimMapping(): string
    {
        $config = ['flavor' => $this->flavor];

        switch ($this->flavor) {
            case 'auth0':
                $domain = trim((string) ($this->post['auth0_domain'] ?? ''));
                if ($domain !== '') {
                    $config['domain'] = $domain;
                }
                $audience = trim((string) ($this->post['auth0_audience'] ?? ''));
                if ($audience !== '') {
                    $config['audience'] = $audience;
                }
                break;

            case 'cognito':
                foreach (['region', 'user_pool_id', 'hosted_ui_domain'] as $k) {
                    $v = trim((string) ($this->post[$k] ?? ''));
                    if ($v !== '') {
                        $config[$k] = $v;
                    }
                }
                break;

            case 'saml':
                $config['flavor'] = 'saml';
                foreach (['entity_id', 'nameid_format', 'idp_x509_cert', 'sp_x509_cert', 'sp_private_key'] as $k) {
                    $v = trim((string) ($this->post[$k] ?? ''));
                    if ($v !== '') {
                        $config[$k] = $v;
                    }
                }
                break;
        }

        $rawJson = (string) ($this->post['claim_mapping_raw'] ?? '{}');
        $decoded = json_decode($rawJson, true);
        if (!is_array($decoded)) {
            $decoded = [];
        }
        unset($decoded['_config']);
        $decoded['_config'] = $config;

        return (string) json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** Derive issuer URL from flavor-specific fields when the issuer input is blank. */
    private function deriveIssuer(): string
    {
        switch ($this->flavor) {
            case 'auth0':
                $domain = trim((string) ($this->post['auth0_domain'] ?? ''));
                return $domain !== '' ? 'https://' . $domain . '/.well-known/openid-configuration' : '';

            case 'cognito':
                $region  = trim((string) ($this->post['region'] ?? ''));
                $poolId  = trim((string) ($this->post['user_pool_id'] ?? ''));
                return ($region !== '' && $poolId !== '')
                    ? "https://cognito-idp.{$region}.amazonaws.com/{$poolId}/.well-known/openid-configuration"
                    : '';
        }
        return '';
    }

    private function mapFlavorToType(string $flavor): string
    {
        return $flavor === 'saml' ? 'saml' : 'oidc';
    }

    private function extractFlavor(array $provider): string
    {
        $raw = $provider['claim_mapping'] ?? '{}';
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        if (is_array($decoded) && isset($decoded['_config']['flavor']) && is_string($decoded['_config']['flavor'])) {
            return $decoded['_config']['flavor'];
        }
        return (string) ($provider['type'] ?? 'oidc');
    }

    private function extractConfig(array $provider): array
    {
        $raw = $provider['claim_mapping'] ?? '{}';
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        if (is_array($decoded) && isset($decoded['_config']) && is_array($decoded['_config'])) {
            return $decoded['_config'];
        }
        return [];
    }

    private function computeUrls(int $id): void
    {
        $this->callbackUrl = $this->baseUrl . '/auth/callback/' . $id;
        $this->acsUrl      = $this->baseUrl . '/auth/saml/acs/' . $id;
        $this->slsUrl      = $this->baseUrl . '/auth/saml/sls/' . $id;
    }

    private function computeBaseUrl(): string
    {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return rtrim($proto . '://' . $host, '/');
    }

    private function getEncryptor(): ?SecretEncryptor
    {
        $appKey = (string) (getenv('APP_KEY') ?: '');
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
        if ($this->mode === 'select') {
            require_once 'authExt/template/provider_select.php';
        } else {
            require_once 'authExt/template/provider_form.php';
        }
    }

    public function onRoot(): bool
    {
        return true;
    }

    public function getTitle(): string
    {
        return match ($this->mode) {
            'select' => 'Add Auth Provider',
            'edit'   => 'Edit Auth Provider',
            default  => 'Add Auth Provider',
        };
    }

    public function getContent(): \Closure
    {
        return $this->content;
    }
}
