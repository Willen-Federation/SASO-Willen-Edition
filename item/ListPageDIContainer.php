<?php
namespace saso\item;

use saso\framework\DIContainer;
use saso\framework\View;

final class ListPageDIContainer implements DIContainer
{
    private View $view;
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->view = new ListPageView($inside);
    }
    public function flow(): View
    {
        return $this->view;
    }
}
