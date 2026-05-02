<?php
namespace saso\shelf;

use saso\framework\DIContainer;
use saso\framework\View;

final class MapDIContainer implements DIContainer
{
    public function isTopLevel(): bool
    {
        return false;
    }

    private array $pins = [];

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $pdo = \saso\repository\DBConnection::getPdo();
        $repo = new \Saso\Infrastructure\StorageLocation\PdoStorageLocationRepository($pdo);
        $this->pins = $repo->listPinned();
    }

    public function flow(): View
    {
        $view = new MapView();
        $view->pins = $this->pins;
        return $view;
    }
}
