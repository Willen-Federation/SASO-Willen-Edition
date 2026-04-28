<?php
namespace saso\featureAdmin;

use Saso\Application\Auth\AdminGuard;
use saso\framework\Setter;
use saso\framework\View;
use saso\repository\DBConnection;

/**
 * Read-only Feature Flag list. Editing flags currently goes through the
 * REST CRUD at /api/v1/feature-flags or the MCP tools — toggling from
 * this screen is a follow-up.
 */
final class ListView implements View
{
    use Setter;
    private \Closure $content;
    /** @var list<array{key:string,description:string,enabled:bool,rolloutPercent:int,autoDisabledAt:?string}> */
    public array $flags = [];
    public bool $authorized = false;

    public function __construct()
    {
        $pdo = null;
        try {
            $pdo = DBConnection::getPdo();
        } catch (\Throwable) {
            $pdo = null;
        }
        $this->authorized = $pdo === null
            ? false
            : (new AdminGuard($pdo))->isAdmin(
                isset($_SESSION['id']) && is_string($_SESSION['id']) ? $_SESSION['id'] : null,
            );
        $this->flags = $this->load();
    }

    public function display(): void
    {
        require_once 'featureAdmin/template/list.php';
    }

    public function onRoot(): bool
    {
        return true;
    }

    public function getTitle(): string
    {
        return __('ui.feature_flags.title', [], null, 'Feature flags');
    }

    public function getContent(): \Closure
    {
        return $this->content;
    }

    /**
     * @return list<array{key:string,description:string,enabled:bool,rolloutPercent:int,autoDisabledAt:?string}>
     */
    private function load(): array
    {
        try {
            $pdo = DBConnection::getPdo();
            $stmt = $pdo->query(
                'SELECT key_name, description, enabled, rollout_percent, auto_disabled_at'
                .' FROM feature_flag ORDER BY key_name ASC'
            );
            if ($stmt === false) {
                return [];
            }
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            if (!is_array($rows)) {
                return [];
            }
            $out = [];
            foreach ($rows as $r) {
                $out[] = [
                    'key'             => (string) ($r['key_name'] ?? ''),
                    'description'     => (string) ($r['description'] ?? ''),
                    'enabled'         => (bool) ($r['enabled'] ?? false),
                    'rolloutPercent'  => (int) ($r['rollout_percent'] ?? 0),
                    'autoDisabledAt'  => isset($r['auto_disabled_at']) ? (string) $r['auto_disabled_at'] : null,
                ];
            }
            return $out;
        } catch (\Throwable) {
            return [];
        }
    }
}
