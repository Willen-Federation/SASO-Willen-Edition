<?php

namespace saso\authExt;

use Saso\Application\Auth\AdminGuard;
use saso\framework\Setter;
use saso\framework\View;
use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;
use saso\repository\DBConnection;

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
    public string $messageVariant = 'danger';
    public string $title = '';
    public bool $hasSecret = false;
    public string $flavor = 'oidc';

    /** Computed URLs for display */
    public string $callbackUrl = '';
    public string $acsUrl = '';
    public string $slsUrl = '';
    public string $loginUrl = '';

    public function __construct(private array $query, private array $post)
    {
        // AJAX verify endpoint — must come before everything else
        // Note: ?action=verify is a query-string param → $_GET, not the framework $query array
        if (($_GET['action'] ?? '') === 'verify'
            && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyHandler();
            return;
        }

        $pdo = DBConnection::getPdo();
        $this->authorized = (new AdminGuard($pdo))->isAdmin(
            isset($_SESSION['id']) && is_string($_SESSION['id']) ? $_SESSION['id'] : null,
        );

        if (!$this->authorized) {
            return;
        }

        // Pop one-shot flash hint (e.g. "now enter credentials") set by an earlier POST.
        if (isset($_SESSION['flash.provider_new']) && is_string($_SESSION['flash.provider_new'])) {
            $this->message = $_SESSION['flash.provider_new'];
            $this->messageVariant = 'info';
            unset($_SESSION['flash.provider_new']);
        }

        // Always compute base URLs (loginUrl is needed even for new mode)
        $this->computeUrls(0);

        // Determine mode: edit or new
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
            $this->computeUrls((int) $this->provider['id']);
        }

        // Delete action — requires POST to prevent CSRF via link prefetch
        if (isset($this->query['delete']) && is_numeric($this->query['delete'])
            && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $stmt = $pdo->prepare('DELETE FROM auth_provider WHERE id = :id');
            $stmt->bindValue(':id', (int) $this->query['delete']);
            $stmt->execute();
            \saso\util\Redirect::redirect('auth/providers/?deleted=1');
            exit;
        }

        // Form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name             = (string) ($this->post['name'] ?? '');
            $type             = (string) ($this->post['type'] ?? 'oidc');
            // The first-step "Create provider" submit has no credentials yet, so
            // we *must* leave new rows disabled — otherwise the half-configured
            // shell shows up on the sign-in page and clicking it throws
            // ProviderMisconfiguredException. The edit mode form defaults the
            // checkbox to checked when !hasSecret so the second submit (with
            // credentials) flips enabled=1 in one click.
            $enabled          = $this->mode === 'edit'
                ? (isset($this->post['enabled']) ? 1 : 0)
                : 0;
            $is_default       = isset($this->post['is_default']) ? 1 : 0;
            $issuer           = (string) ($this->post['issuer_or_metadata_url'] ?? '');
            $client_id        = (string) ($this->post['client_id'] ?? '');
            $scopes           = (string) ($this->post['scopes'] ?? '');
            $client_secret_raw = (string) ($this->post['client_secret'] ?? '');

            $claim_mapping = $this->buildClaimMapping($type);

            if ($name === '') {
                $this->message = 'Name is required.';
                $this->messageVariant = 'danger';
            } elseif ($this->mode === 'edit'
                && $type === 'oidc'
                && !$this->hasSecret
                && $client_secret_raw === ''
            ) {
                // Brand-new shell row that has never had a secret; refuse to save
                // so the provider cannot be enabled in a misconfigured state.
                $this->message = 'Client Secret is required.';
                $this->messageVariant = 'danger';
            } else {
                if ($is_default) {
                    $pdo->exec('UPDATE auth_provider SET is_default = 0');
                }

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
                    $stmt->bindValue(':id', (int) $this->provider['id']);
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

                if ($this->mode === 'edit') {
                    \saso\util\Redirect::redirect('auth/providers/?saved=1');
                } else {
                    $newId = (int) $pdo->lastInsertId();
                    $_SESSION['flash.provider_new'] = 'Now enter the client credentials and Save again to activate this provider.';
                    // Use path-style edit URL — the legacy router parses
                    // /auth/provider/edit/{id} into $query['edit']={id}, but
                    // /auth/provider/?edit={id} is NOT recognised (the query
                    // string is dropped before the action map runs).
                    \saso\util\Redirect::redirect('auth/provider/edit/'.$newId);
                }
                exit;
            }
        }

        // Resolve flavor from saved claim_mapping
        $claimData = json_decode((string) ($this->provider['claim_mapping'] ?? '{}'), true);
        $this->flavor = (string) ((is_array($claimData) ? ($claimData['_config']['flavor'] ?? '') : '') ?: 'oidc');
    }

    private function buildClaimMapping(string $type): string
    {
        $config = [];

        if ($type === 'oidc') {
            $flavor = (string) ($this->post['flavor'] ?? 'oidc');
            $config['flavor'] = $flavor;

            if ($flavor === 'auth0') {
                $d = trim((string) ($this->post['auth0_domain'] ?? ''));
                if ($d === '') {
                    $d = self::hostFromUrl((string) ($this->post['issuer_or_metadata_url'] ?? ''));
                }
                if ($d !== '') {
                    $config['domain'] = $d;
                }
                $a = trim((string) ($this->post['auth0_audience'] ?? ''));
                if ($a !== '') {
                    $config['audience'] = $a;
                }
            } elseif ($flavor === 'cognito') {
                $r = trim((string) ($this->post['cognito_region'] ?? ''));
                if ($r !== '') {
                    $config['region'] = $r;
                }
                $p = trim((string) ($this->post['cognito_user_pool_id'] ?? ''));
                if ($p !== '') {
                    $config['user_pool_id'] = $p;
                }
                $h = trim((string) ($this->post['cognito_hosted_ui_domain'] ?? ''));
                if ($h !== '') {
                    $config['hosted_ui_domain'] = $h;
                }
            } elseif ($flavor === 'firebase') {
                $pid = trim((string) ($this->post['firebase_project_id'] ?? ''));
                if ($pid !== '') {
                    $config['project_id'] = $pid;
                }
                $hd = trim((string) ($this->post['firebase_hd'] ?? ''));
                if ($hd !== '') {
                    $config['hd'] = $hd;
                }
                $providers = $this->post['firebase_providers'] ?? [];
                if (is_array($providers) && $providers !== []) {
                    $config['firebase_providers'] = array_values(
                        array_filter($providers, fn ($p) => is_string($p) && $p !== '')
                    );
                }
            }
        } elseif ($type === 'saml') {
            $config['flavor'] = 'saml';
            $entityId = (string) ($this->post['entity_id'] ?? '');
            if ($entityId !== '') {
                $config['entity_id'] = $entityId;
            }
            $nameidFormat = (string) ($this->post['nameid_format'] ?? '');
            if ($nameidFormat !== '') {
                $config['nameid_format'] = $nameidFormat;
            }
            $idpCert = (string) ($this->post['idp_x509_cert'] ?? '');
            if ($idpCert !== '') {
                $config['idp_x509_cert'] = $idpCert;
            }
            $spCert = (string) ($this->post['sp_x509_cert'] ?? '');
            if ($spCert !== '') {
                $config['sp_x509_cert'] = $spCert;
            }
            $spKey = (string) ($this->post['sp_private_key'] ?? '');
            if ($spKey !== '') {
                $config['sp_private_key'] = $spKey;
            }
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

    private function verifyHandler(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $token = (string) ($this->post['csrftoken'] ?? '');
        if (!\saso\util\CSRFtoken::verify($token)) {
            echo json_encode(['ok' => false, 'error' => 'CSRF token invalid']);
            exit;
        }

        $pdo = DBConnection::getPdo();
        $authorized = (new AdminGuard($pdo))->isAdmin(
            isset($_SESSION['id']) && is_string($_SESSION['id']) ? $_SESSION['id'] : null,
        );
        if (!$authorized) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Forbidden']);
            exit;
        }

        $type   = (string) ($this->post['type'] ?? 'oidc');
        $issuer = trim((string) ($this->post['issuer_or_metadata_url'] ?? ''));

        if ($issuer === '') {
            echo json_encode(['ok' => false, 'error' => 'No issuer / metadata URL provided']);
            exit;
        }

        if ($type === 'oidc') {
            $this->verifyOidc($issuer);
        } else {
            $this->verifySaml($issuer);
        }
    }

    private function fetchUrl(string $url): array
    {
        $lastError = '';
        set_error_handler(static function (int $errno, string $errstr) use (&$lastError): bool {
            $lastError = $errstr;

            return true;
        });

        $ctx  = stream_context_create([
            'http' => ['timeout' => 10, 'ignore_errors' => true],
        ]);
        $body = file_get_contents($url, false, $ctx);
        restore_error_handler();

        $httpStatus = '';
        if (isset($http_response_header) && is_array($http_response_header)) {
            $httpStatus = $http_response_header[0] ?? '';
        }

        return [$body, $httpStatus, $lastError];
    }

    private function verifyOidc(string $issuer): void
    {
        $discoveryUrl = rtrim($issuer, '/');
        if (!str_ends_with($discoveryUrl, '/openid-configuration')) {
            $discoveryUrl .= '/.well-known/openid-configuration';
        }

        [$body, $httpStatus, $phpError] = $this->fetchUrl($discoveryUrl);

        if ($body === false) {
            $reason = $phpError !== '' ? $phpError : 'connection failed';
            echo json_encode(['ok' => false, 'error' => 'Could not reach '.$discoveryUrl.' — '.$reason]);
            exit;
        }

        $json = json_decode($body, true);
        if (!is_array($json)) {
            $preview = substr($body, 0, 300);
            echo json_encode([
                'ok'    => false,
                'error' => 'Discovery URL returned non-JSON ('.$httpStatus.'). Response preview: '.$preview,
            ]);
            exit;
        }

        if (empty($json['authorization_endpoint'])) {
            $keys = implode(', ', array_keys($json));
            echo json_encode([
                'ok'    => false,
                'error' => 'Discovery document missing authorization_endpoint ('.$httpStatus.'). Keys present: '.$keys,
            ]);
            exit;
        }

        $clientId   = trim((string) ($this->post['client_id'] ?? ''));
        $providerId = (int) ($this->post['provider_id'] ?? 0);
        $authUrl    = null;
        if ($clientId !== '' && $providerId > 0) {
            $proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $base    = rtrim($proto . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');
            $authUrl = $json['authorization_endpoint'] . '?' . http_build_query([
                'client_id'     => $clientId,
                'redirect_uri'  => $base . '/auth/callback',
                'response_type' => 'code',
                'scope'         => 'openid profile email',
                'state'         => bin2hex(random_bytes(8)),
            ]);
        }
        echo json_encode([
            'ok'       => true,
            'detail'   => 'authorization_endpoint: ' . $json['authorization_endpoint'],
            'auth_url' => $authUrl,
        ]);
        exit;
    }

    private function verifySaml(string $metadataUrl): void
    {
        [$body, $httpStatus, $phpError] = $this->fetchUrl($metadataUrl);

        if ($body === false) {
            $reason = $phpError !== '' ? $phpError : 'connection failed';
            echo json_encode(['ok' => false, 'error' => 'Could not reach '.$metadataUrl.' — '.$reason]);
            exit;
        }

        if (!str_contains($body, 'EntityDescriptor')) {
            $preview = substr($body, 0, 300);
            echo json_encode([
                'ok'    => false,
                'error' => 'Response is not SAML metadata — no EntityDescriptor found ('.$httpStatus.'). Preview: '.$preview,
            ]);
            exit;
        }

        echo json_encode(['ok' => true, 'detail' => 'Metadata reachable and contains EntityDescriptor ('.$httpStatus.')']);
        exit;
    }

    private function computeUrls(int $id): void
    {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base  = rtrim($proto.'://'.$host, '/');

        $this->loginUrl = $base.'/auth/login';

        // NOTE: Callback URLs do NOT include the provider ID (security measure).
        // The provider ID is stored in the session during login and retrieved from
        // there during callback handling. This prevents provider enumeration attacks.
        if ($id > 0) {
            $this->callbackUrl = $base.'/auth/callback';
            $this->acsUrl      = $base.'/auth/saml/acs';
            $this->slsUrl      = $base.'/auth/saml/sls';
        }
    }

    private static function hostFromUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        $host = parse_url($url, PHP_URL_HOST);
        return is_string($host) ? $host : '';
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
