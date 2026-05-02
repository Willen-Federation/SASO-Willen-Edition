<?php
namespace saso\shelf;

use saso\framework\DIContainer;
use saso\framework\View;

final class SimpleDIContainer implements DIContainer
{
    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
    }

    public function flow(): View
    {
        $view = new SimpleView();
        $view->title = 'Quick Shelf Setup';
        $view->content = fn() => null;
        return $view;
    }
}
