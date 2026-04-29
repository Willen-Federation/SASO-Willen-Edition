<?php
namespace saso\admin;

use saso\framework\DIContainer;
use saso\framework\View;

final class AuthProvidersDIContainer implements DIContainer
{
    public function isTopLevel(): bool
    {
        return true;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        try {
            $pdo = \saso\repository\DBConnection::getPdo();
            $stmt = $pdo->query("SELECT id, name, type, enabled, issuer_or_metadata_url as issuerUrl, client_id as clientId FROM auth_provider ORDER BY is_default DESC, name ASC");
            $providers = $stmt ? $stmt->fetchAll(\PDO::FETCH_OBJ) : [];
        } catch (\Throwable $e) {
            $providers = [];
        }

        $this->view = new AuthProvidersView();
        $this->view->providers = $providers;
    }

    public function flow(): View
    {
        return $this->view;
    }
}
