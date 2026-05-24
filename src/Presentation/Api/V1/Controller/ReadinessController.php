<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller;

use Closure;
use PDO;
use PDOException;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;
use Throwable;

/**
 * Readiness probe for `/api/v1/health/readiness`.
 *
 * Unlike `/health` (which is a pure liveness check), this endpoint
 * actively touches the configured database to answer "is the deployment
 * fully wired and migrated?" — the question operators ask when an
 * orchestrator says "running" but the API still returns 500s.
 *
 * The set of checks below is deliberately conservative: every probe is a
 * cheap `information_schema` lookup or a `SELECT 1`, so this endpoint is
 * safe to hit from a monitoring loop. We do not query rows, mutate state,
 * or open transactions.
 *
 * Response shape (always JSON):
 *
 *   {
 *     "status": "ready" | "degraded",
 *     "checks": [
 *       {"name": "database.connect", "status": "ok"},
 *       {"name": "schema.item.note",  "status": "missing", "detail": "..."}
 *     ],
 *     "time": "RFC3339"
 *   }
 *
 * Status mapping:
 *   - 200 OK   when every check is `ok`
 *   - 503 SUE  when one or more checks failed (the operator should
 *              consult the `checks` list to know which migration / env
 *              variable to fix)
 *
 * The PDO factory is a closure rather than a direct `PDO` parameter so a
 * connection failure (bad DSN, credentials, server down) shows up here as
 * a structured check failure instead of an uncaught exception at boot.
 */
final class ReadinessController
{
    /**
     * Schema invariants the API surface relies on. If any of these is
     * missing the corresponding feature endpoint will 500. List grows as
     * migrations introduce new required columns / tables — kept in this
     * file (not pulled from `information_schema` dynamically) so the
     * probe is decoupled from the live schema's drift.
     *
     * Column names mirror the actual Phinx migrations (cf.
     * migrations/M4/* and migrations/M6/*). Drift here masks real schema
     * problems behind a permanent "degraded" — every entry must match a
     * runtime SQL site (PdoFeatureFlagRepository, PdoPairingCodeRepository,
     * PdoDeviceTokenRepository, etc.).
     *
     * @var list<array{table: string, columns: list<string>}>
     */
    private const REQUIRED_SCHEMA = [
        ['table' => 'item', 'columns' => ['id', 'name', 'jan_code', 'isbn', 'label_code', 'note', 'status', 'storage_location_id']],
        ['table' => 'category', 'columns' => ['id', 'name_ja']],
        ['table' => 'storage_location', 'columns' => ['id', 'name']],
        ['table' => 'auth_provider', 'columns' => ['id', 'type', 'enabled']],
        ['table' => 'feature_flag', 'columns' => ['key_name', 'enabled']],
        ['table' => 'pairing_code', 'columns' => ['token_hash', 'expires_at', 'member_id']],
        ['table' => 'device_token', 'columns' => ['id', 'token_hash', 'member_id', 'scopes']],
    ];

    /**
     * @param Closure(): PDO $pdoFactory Lazy PDO factory; called once per
     *                                   request, exceptions are captured.
     */
    public function __construct(
        private readonly Closure $pdoFactory,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $checks = [];

        $pdo = null;
        try {
            $pdo = ($this->pdoFactory)();
            $checks[] = ['name' => 'database.connect', 'status' => 'ok'];
        } catch (PDOException $e) {
            $checks[] = [
                'name'   => 'database.connect',
                'status' => 'failed',
                'detail' => self::redact($e->getMessage()),
            ];
        } catch (Throwable $e) {
            $checks[] = [
                'name'   => 'database.connect',
                'status' => 'failed',
                'detail' => self::redact($e->getMessage()),
            ];
        }

        if ($pdo instanceof PDO) {
            $checks[] = $this->probePing($pdo);
            foreach (self::REQUIRED_SCHEMA as $expect) {
                foreach ($this->probeTable($pdo, $expect['table'], $expect['columns']) as $entry) {
                    $checks[] = $entry;
                }
            }
        }

        $allOk = true;
        foreach ($checks as $c) {
            if ($c['status'] !== 'ok') {
                $allOk = false;
                break;
            }
        }

        return new JsonResponse(
            status: $allOk ? 200 : 503,
            body: [
                'status' => $allOk ? 'ready' : 'degraded',
                'checks' => $checks,
                'time'   => gmdate(\DateTimeInterface::RFC3339),
            ],
        );
    }

    /**
     * @return array{name: string, status: string, detail?: string}
     */
    private function probePing(PDO $pdo): array
    {
        try {
            $stmt = $pdo->query('SELECT 1');
            if ($stmt === false) {
                return ['name' => 'database.ping', 'status' => 'failed', 'detail' => 'query returned false'];
            }
            $stmt->fetch();
            return ['name' => 'database.ping', 'status' => 'ok'];
        } catch (PDOException $e) {
            return [
                'name'   => 'database.ping',
                'status' => 'failed',
                'detail' => self::redact($e->getMessage()),
            ];
        }
    }

    /**
     * @param list<string> $expectedColumns
     *
     * @return list<array{name: string, status: string, detail?: string}>
     */
    private function probeTable(PDO $pdo, string $table, array $expectedColumns): array
    {
        $entries = [];

        try {
            $present = $this->columnsOf($pdo, $table);
        } catch (PDOException $e) {
            $entries[] = [
                'name'   => sprintf('schema.%s', $table),
                'status' => 'failed',
                'detail' => self::redact($e->getMessage()),
            ];

            return $entries;
        }

        if (empty($present)) {
            $entries[] = [
                'name'   => sprintf('schema.%s', $table),
                'status' => 'missing',
                'detail' => 'table not found — migrations may not be applied',
            ];

            return $entries;
        }

        $entries[] = ['name' => sprintf('schema.%s', $table), 'status' => 'ok'];

        $presentLower = array_map(strtolower(...), $present);
        foreach ($expectedColumns as $col) {
            if (!in_array(strtolower($col), $presentLower, true)) {
                $entries[] = [
                    'name'   => sprintf('schema.%s.%s', $table, $col),
                    'status' => 'missing',
                    'detail' => 'column not found — migrations may not be applied',
                ];
            }
        }

        return $entries;
    }

    /**
     * Driver-aware column listing.
     *
     * Production runs MariaDB / MySQL, where `information_schema.columns`
     * is the portable answer. The unit-test suite, however, drives this
     * controller against an in-memory SQLite database — which exposes the
     * same data via `PRAGMA table_info(...)`. Branch on the driver name
     * so tests don't need a real MySQL instance and so the controller
     * works for whichever database the deployment is configured against.
     *
     * @return list<string>
     */
    private function columnsOf(PDO $pdo, string $table): array
    {
        $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            // SQLite identifier quoting is the standard double-quote form;
            // the table name comes from a hard-coded allow-list in
            // self::REQUIRED_SCHEMA so injection is not a concern here.
            $stmt = $pdo->query(sprintf('PRAGMA table_info("%s")', str_replace('"', '""', $table)));
            if ($stmt === false) {
                return [];
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return array_map(
                static fn (array $r): string => (string) ($r['name'] ?? ''),
                $rows,
            );
        }

        $stmt = $pdo->prepare(
            'SELECT column_name FROM information_schema.columns '
            .'WHERE table_schema = DATABASE() AND table_name = :table',
        );
        $stmt->execute(['table' => $table]);

        return array_map(
            static fn (array $r): string => (string) ($r['column_name'] ?? $r['COLUMN_NAME'] ?? ''),
            $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [],
        );
    }

    /**
     * Strip credentials, infrastructure topology and filesystem paths
     * before they leave the server. The readiness endpoint is reachable
     * by anonymous orchestrator probes, so PDOException text must never
     * disclose:
     *   - DSN key/value pairs (user, password, host, port, dbname,
     *     unix_socket, charset, …)
     *   - MySQL's `'user'@'host'` quoted credential format
     *   - Bare IPv4/IPv6 addresses (covers connection refused / DNS messages)
     *   - Absolute filesystem paths (PHP exceptions include __FILE__)
     */
    private static function redact(string $message): string
    {
        // DSN-style key=value fragments. The first list is anything we
        // treat as a credential or topology identifier; the catch-all
        // afterwards trims known DSN keys to keep the response useful
        // while still scrubbing the value.
        $message = preg_replace(
            '/(password|pwd|secret|key|user|username|uid|host|hostname|port|dbname|database|unix_socket|socket|charset)=([^;\s]+)/i',
            '$1=***',
            $message,
        ) ?? $message;

        // MariaDB / MySQL "Access denied for user 'foo'@'10.0.0.1'" form.
        // Quotes may be backticks, single, or double.
        $message = preg_replace(
            '/([`\'"])[^`\'"]+\1\s*@\s*([`\'"])[^`\'"]+\2/',
            "'***'@'***'",
            $message,
        ) ?? $message;

        // Raw IPv4 and the common IPv6 loopback/link-local fragments.
        $message = preg_replace(
            '/\b(?:\d{1,3}\.){3}\d{1,3}(?::\d+)?\b/',
            '***',
            $message,
        ) ?? $message;
        $message = preg_replace(
            '/\b(?:[0-9a-f]{1,4}:){2,7}[0-9a-f]{1,4}\b/i',
            '***',
            $message,
        ) ?? $message;

        // Absolute POSIX filesystem paths (stack-trace leftovers).
        $message = preg_replace('#(?<![\w/])/[\w./\-]+#', '***', $message) ?? $message;

        if (strlen($message) > 240) {
            $message = substr($message, 0, 237).'...';
        }

        return $message;
    }
}
