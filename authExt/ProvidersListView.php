<?php
namespace saso\authExt;

use Saso\Application\Auth\AdminGuard;
use saso\framework\Setter;
use saso\framework\View;
use saso\repository\DBConnection;

/**
 * Admin Auth Providers list view. Reads `auth_provider` rows directly so
 * the screen works whether or not the new src/Application layer is fully
 * wired into the legacy DI tree yet — the only authoritative state lives
 * in the DB anyway.
 */
final class ProvidersListView implements View
{
    use Setter;
    private \Closure $content;
    /** @var list<array{id:int,name:string,type:string,flavor:string,enabled:bool,is_default:bool,issuer:?string}> */
    public array $providers = [];
    public bool $authorized = false;

    public function __construct()
    {
        $this->providers  = $this->loadProviders();
        $this->authorized = (new AdminGuard(DBConnection::getPdo()))->isAdmin(
            isset($_SESSION['id']) && is_string($_SESSION['id']) ? $_SESSION['id'] : null,
        );
    }

    public function display(): void
    {
        require_once 'authExt/template/providers_list.php';
    }

    public function onRoot(): bool
    {
        return true;
    }

    public function getTitle(): string
    {
        return __('ui.auth_providers.title', [], null, 'Authentication providers');
    }

    public function getContent(): \Closure
    {
        return $this->content;
    }

    /**
     * @return list<array{id:int,name:string,type:string,flavor:string,enabled:bool,is_default:bool,issuer:?string}>
     */
    private function loadProviders(): array
    {
        try {
            $pdo = DBConnection::getPdo();
            $stmt = $pdo->query(
                'SELECT id, name, type, enabled, is_default, issuer_or_metadata_url, claim_mapping'
                .' FROM auth_provider ORDER BY is_default DESC, name ASC'
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
                $flavor = (string) ($r['type'] ?? 'oidc');
                if (isset($r['claim_mapping']) && is_string($r['claim_mapping'])) {
                    $decoded = json_decode($r['claim_mapping'], true);
                    if (is_array($decoded) && isset($decoded['_config']['flavor']) && is_string($decoded['_config']['flavor'])) {
                        $flavor = $decoded['_config']['flavor'];
                    }
                }
                $out[] = [
                    'id'         => (int) ($r['id'] ?? 0),
                    'name'       => (string) ($r['name'] ?? ''),
                    'type'       => (string) ($r['type'] ?? ''),
                    'flavor'     => $flavor,
                    'enabled'    => (bool) ($r['enabled'] ?? false),
                    'is_default' => (bool) ($r['is_default'] ?? false),
                    'issuer'     => isset($r['issuer_or_metadata_url']) && is_string($r['issuer_or_metadata_url'])
                        ? $r['issuer_or_metadata_url']
                        : null,
                ];
            }
            return $out;
        } catch (\Throwable) {
            return [];
        }
    }
}
