<?php
namespace saso\shelf;

use saso\framework\DIContainer;
use saso\framework\View;

final class SimpleSaveDIContainer implements DIContainer
{
    private View $view;

    public function isTopLevel(): bool
    {
        return true;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $pdo = \saso\repository\DBConnection::getPdo();
        $repo = new \Saso\Infrastructure\StorageLocation\PdoStorageLocationRepository($pdo);
        
        $usecase = new SimpleSaveUsecase($repo);
        $ctrl = new SimpleSaveController($usecase);
        
        $this->view = $ctrl->handle($post);
    }

    public function flow(): View
    {
        return $this->view;
    }
}
