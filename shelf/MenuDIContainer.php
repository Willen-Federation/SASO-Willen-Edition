<?php
namespace saso\shelf;

use saso\framework\DIContainer;
use saso\framework\View;

final class MenuDIContainer implements DIContainer
{
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside , array $query, array $post, array $config, \DateTime $now): void
    {
    }
    public function flow(): View
    {
        $view = new MenuView();
        // Metadata will be loaded lazily in the template if needed
        return $view;
    }
}
