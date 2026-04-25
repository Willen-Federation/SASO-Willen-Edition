<?php
namespace saso\start;

use saso\framework\DIContainer;
use saso\framework\View;

final class StartDIContainer implements DIContainer
{
    private View $view;
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->view = new StartView(
            $inside,
        );
    }
    public function flow(): View
    {
        return $this->view;
    }
}
