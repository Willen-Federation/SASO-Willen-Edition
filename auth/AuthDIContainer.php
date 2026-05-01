<?php
namespace saso\auth;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DBConnection;
use saso\repository\DbFinder;
use saso\repository\DbUpdater;

final class AuthDIContainer implements DIContainer
{
    use Flow;
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        if(empty($post)) {
            $view = new AuthView();
            $view->idpProviders = $this->loadIdpProviders();
            $view->providerError = ($_GET['error'] ?? '') === 'auth_unavailable';
            $this->ctrl = new AuthController($query);
            $this->usecase = new common\EmptyUsecase(
                new AuthPresenter($view),
            );
        } else {
            $this->ctrl = new AuthController($query, new LoginController($post));
            $this->usecase = new LoginUsecase(
                new DbFinder(),
                new DbUpdater(),
                new LoginPresenter(
                    new LoginView(),
                )
            );
        }
    }

    /**
     * Loads enabled non-local providers for the login screen. Schemas that
     * have not yet run M4 migrations (no `auth_provider` table) silently
     * fall back to an empty list — the legacy username/password form keeps
     * working unchanged.
     *
     * @return list<array{id:string,name:string,flavor:string,type:string}>
     */
    private function loadIdpProviders(): array
    {
        try {
            $pdo = DBConnection::getPdo();
            $stmt = $pdo->query(
                "SELECT id, name, type, claim_mapping FROM auth_provider"
                ." WHERE enabled = 1 AND type != 'local'"
                ." ORDER BY is_default DESC, name ASC"
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
                $cfg = [];
                if (isset($r['claim_mapping']) && is_string($r['claim_mapping'])) {
                    $decoded = json_decode($r['claim_mapping'], true);
                    if (is_array($decoded) && isset($decoded['_config']) && is_array($decoded['_config'])) {
                        $cfg = $decoded['_config'];
                    }
                }
                $out[] = [
                    'id'     => (string) ($r['id'] ?? ''),
                    'name'   => (string) ($r['name'] ?? ''),
                    'flavor' => isset($cfg['flavor']) && is_string($cfg['flavor']) ? $cfg['flavor'] : (string) ($r['type'] ?? 'oidc'),
                    'type'   => (string) ($r['type'] ?? 'oidc'),
                ];
            }
            return $out;
        } catch (\Throwable) {
            return [];
        }
    }
}
