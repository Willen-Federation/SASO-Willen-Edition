<?php

namespace saso\auth;

use Saso\Infrastructure\Auth\Crypto\SecretEncryptor;
use Saso\Infrastructure\Auth\Repository\PdoAuthProviderRepository;
use saso\common\EmptyUsecase;
use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DBConnection;

final class ProviderNewDIContainer implements DIContainer
{
    use Flow;

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        // AJAX probe used by the wizard's "接続をテスト" button. Reuses the
        // same builder methods the save flow does, so the URL we probe is
        // the URL we would persist. Echoes JSON and exits before the
        // framework's controller/usecase pipeline runs.
        if (($_GET['action'] ?? '') === 'test'
            && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            self::handleTestConnection($post);
            exit;
        }

        if (empty($post)) {
            $this->ctrl    = new ProviderNewController($query);
            $this->usecase = new EmptyUsecase(
                new ProviderNewPresenter(
                    new ProviderNewView(),
                ),
            );
        } else {
            $pdo       = DBConnection::getPdo();
            $encryptor = self::buildEncryptor();
            $repo      = new PdoAuthProviderRepository($pdo, $encryptor);

            $this->ctrl    = new ProviderSaveController($post);
            $this->usecase = new ProviderSaveUsecase(
                $repo,
                $encryptor,
                new ProviderSavePresenter(
                    new ProviderNewView(),
                ),
            );
        }
    }

    /**
     * @param array<string, mixed> $post
     */
    private static function handleTestConnection(array $post): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $token = (string) ($post['csrftoken'] ?? '');
        if (!\saso\util\CSRFtoken::verify($token)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'CSRF token invalid.']);
            return;
        }

        $template = (string) ($post['provider_template'] ?? '');
        $url = match ($template) {
            'auth0'    => ProviderSaveController::buildAuth0IssuerUrl((string) ($post['auth0_domain'] ?? '')),
            'cognito'  => ProviderSaveController::buildCognitoIssuerUrl(
                (string) ($post['cognito_region'] ?? ''),
                (string) ($post['cognito_user_pool_id'] ?? ''),
            ),
            'firebase' => ProviderSaveController::buildFirebaseIssuerUrl((string) ($post['firebase_project_id'] ?? '')),
            'oidc'     => ProviderSaveController::normalizeUrl((string) ($post['oidc_issuer_url'] ?? '')),
            'saml'     => ProviderSaveController::normalizeUrl((string) ($post['saml_metadata_url'] ?? '')),
            default    => '',
        };

        if ($url === '') {
            http_response_code(400);
            echo json_encode([
                'ok'      => false,
                'message' => 'No discovery / metadata URL could be derived from the form.',
            ]);
            return;
        }

        if ($template === 'saml') {
            [$ok, $message, $details] = self::probeSaml($url);
        } else {
            [$ok, $message, $details] = self::probeOidc($url);
        }

        http_response_code($ok ? 200 : 502);
        echo json_encode(array_filter([
            'ok'      => $ok,
            'message' => $message,
            'details' => $details,
        ], static fn ($v): bool => $v !== null));
    }

    /**
     * @return array{bool, string, array<string, mixed>|null}
     */
    private static function probeOidc(string $issuer): array
    {
        $discoveryUrl = rtrim($issuer, '/');
        if (!str_ends_with($discoveryUrl, '/openid-configuration')) {
            $discoveryUrl .= '/.well-known/openid-configuration';
        }

        [$body, $httpStatus, $err] = self::fetch($discoveryUrl);
        if ($body === false) {
            return [false, 'Could not reach '.$discoveryUrl.($err !== '' ? ' — '.$err : ''), null];
        }

        $doc = json_decode($body, true);
        if (!is_array($doc)) {
            return [
                false,
                'Discovery endpoint did not return valid JSON ('.$httpStatus.').',
                ['preview' => substr($body, 0, 200)],
            ];
        }

        $missing = [];
        foreach (['issuer', 'authorization_endpoint', 'token_endpoint'] as $key) {
            if (empty($doc[$key])) {
                $missing[] = $key;
            }
        }
        if ($missing !== []) {
            return [
                false,
                'Discovery document is missing required fields: '.implode(', ', $missing),
                ['issuer' => $doc['issuer'] ?? null],
            ];
        }

        return [
            true,
            'OIDC discovery endpoint reachable. Issuer: '.$doc['issuer'],
            [
                'issuer'                 => $doc['issuer'],
                'authorization_endpoint' => $doc['authorization_endpoint'],
                'token_endpoint'         => $doc['token_endpoint'],
                'end_session_endpoint'   => $doc['end_session_endpoint'] ?? null,
            ],
        ];
    }

    /**
     * @return array{bool, string, array<string, mixed>|null}
     */
    private static function probeSaml(string $url): array
    {
        [$body, $httpStatus, $err] = self::fetch($url);
        if ($body === false) {
            return [false, 'Could not reach '.$url.($err !== '' ? ' — '.$err : ''), null];
        }
        if (!str_contains($body, 'EntityDescriptor')) {
            return [
                false,
                'Response is not SAML metadata — no EntityDescriptor element ('.$httpStatus.').',
                ['preview' => substr($body, 0, 200)],
            ];
        }
        return [true, 'SAML metadata reachable ('.$httpStatus.', '.strlen($body).' bytes).', null];
    }

    /**
     * @return array{0: string|false, 1: string, 2: string}
     */
    private static function fetch(string $url): array
    {
        $err = '';
        set_error_handler(static function (int $errno, string $errstr) use (&$err): bool {
            $err = $errstr;
            return true;
        });

        $ctx = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'timeout'       => 10,
                'ignore_errors' => true,
                'header'        => "Accept: application/json, application/xml, */*\r\nUser-Agent: SASO-Auth-Probe/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        restore_error_handler();

        $status = '';
        if (isset($http_response_header) && is_array($http_response_header) && isset($http_response_header[0])) {
            $status = (string) $http_response_header[0];
        }

        return [$body, $status, $err];
    }

    private static function buildEncryptor(): ?SecretEncryptor
    {
        $raw = getenv('APP_KEY');
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $bytes = base64_decode($raw, strict: true);
        if ($bytes === false) {
            $bytes = hex2bin($raw) ?: null;
        }
        if ($bytes === null || strlen($bytes) !== 32) {
            return null;
        }
        return new SecretEncryptor($bytes);
    }
}
