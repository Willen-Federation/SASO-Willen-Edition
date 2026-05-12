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
        // Map coordinates (mapXRatio/mapYRatio) are not yet stored in the DB.
        // Pass an empty pin list until the feature is fully implemented.
        $this->pins = [];
    }

    public function flow(): View
    {
        $view = new MapView();
        $view->pins = $this->pins;
        return $view;
    }
}
