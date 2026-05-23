<?php

declare(strict_types=1);

namespace saso\installer;

use saso\util\EnvLoader;
use Saso\Infrastructure\Auth\Crypto\AppKeyResolver;

/**
 * Sanity checks that gate the deletion of `installer.json`.
 *
 * Once the wizard hits the "installed" page it normally tears itself down by
 * deleting `installer/installer.json`. If any of the secrets it just wrote
 * are still rejected by the boot path, that teardown would lock the operator
 * into a broken `/api/v1/*` (500 SASO-INFRA-9000) with the wizard route gone.
 * This class is the last line of defence: it re-reads `.env` after the wizard
 * finishes and asserts that AppKeyResolver, the JWT shape check, and the
 * webhook shape check all pass before the install is considered complete.
 *
 * The HTTP self-test (curl against /api/v1/health) is optional and injected
 * via a callable so the unit tests can stub it without needing a running
 * server.
 */
final class PostInstallSelfTest
{
    /** @var (callable(string): array{ok:bool, status:int, error:?string})|null */
    private $httpProbe;

    /**
     * @param (callable(string): array{ok:bool, status:int, error:?string})|null $httpProbe
     *        Optional HTTP fetcher. Returns `{ok, status, error}`. When null,
     *        the HTTP segment of the self-test is skipped (and reported as
     *        such in the result).
     */
    public function __construct(?callable $httpProbe = null)
    {
        $this->httpProbe = $httpProbe;
    }

    /**
     * Run every assertion.
     *
     * @param list<string> $httpUrls URLs to probe (typically /api/v1/health
     *                               and /api/v1/auth/providers). Each must
     *                               return 200 for the test to pass.
     */
    public function run(string $envPath, array $httpUrls = []): SelfTestResult
    {
        $failures = [];

        if (!is_file($envPath)) {
            return SelfTestResult::failed([
                ['key' => 'env_file', 'message' => '.env が見つかりません: '.$envPath],
            ]);
        }

        $env = EnvLoader::loadFile($envPath);

        // APP_KEY — must boot through AppKeyResolver without throwing.
        $appKey = trim((string) ($env[SecurityStep::KEY_APP] ?? ''));
        if (AppKeyResolver::tryResolve($appKey) === null) {
            $failures[] = [
                'key'     => SecurityStep::KEY_APP,
                'message' => 'APP_KEY が base64-32B / hex-32B / 32 文字以上のいずれにも該当しません。',
            ];
        }

        // JWT_SECRET — same 3-shape rule as APP_KEY because Bootstrap derives
        // the JWT HMAC key through the same resolver path.
        $jwt = trim((string) ($env[SecurityStep::KEY_JWT] ?? ''));
        if (!SecurityStep::validate(SecurityStep::KEY_JWT, $jwt)) {
            $failures[] = [
                'key'     => SecurityStep::KEY_JWT,
                'message' => 'JWT_SECRET が無効です。',
            ];
        }

        // WEBHOOK_SECRET — ≥ 32 chars is enough; no AES key derivation.
        $webhook = trim((string) ($env[SecurityStep::KEY_WEBHOOK] ?? ''));
        if (!SecurityStep::validate(SecurityStep::KEY_WEBHOOK, $webhook)) {
            $failures[] = [
                'key'     => SecurityStep::KEY_WEBHOOK,
                'message' => 'WEBHOOK_SECRET が無効です。',
            ];
        }

        // Optional HTTP probe. Each URL must return 200; anything else is
        // either an environment problem (we caught it before deleting
        // installer.json) or a regression in the API that the operator needs
        // to know about before walking away.
        $httpResults = [];
        if ($this->httpProbe !== null) {
            foreach ($httpUrls as $url) {
                $result = ($this->httpProbe)($url);
                $httpResults[$url] = $result;
                if (!$result['ok']) {
                    $failures[] = [
                        'key'     => 'http:'.$url,
                        'message' => sprintf(
                            'HTTP %s が 200 を返しませんでした (status=%d, error=%s)',
                            $url,
                            $result['status'],
                            $result['error'] ?? 'n/a',
                        ),
                    ];
                }
            }
        }

        if (!empty($failures)) {
            return SelfTestResult::failed($failures, $httpResults);
        }
        return SelfTestResult::ok($httpResults);
    }

    /**
     * Default cURL probe. Best-effort: short timeout, no SSL verification
     * because the wizard typically runs against http://127.0.0.1 before a
     * proper TLS cert is provisioned.
     *
     * @return array{ok:bool, status:int, error:?string}
     */
    public static function defaultProbe(string $url): array
    {
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'status' => 0, 'error' => 'curl extension unavailable'];
        }
        $ch = curl_init();
        if ($ch === false) {
            return ['ok' => false, 'status' => 0, 'error' => 'curl_init failed'];
        }
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);
        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch) ?: null;
        curl_close($ch);
        unset($body);
        return [
            'ok'     => $status >= 200 && $status < 300,
            'status' => $status,
            'error'  => $err,
        ];
    }
}
