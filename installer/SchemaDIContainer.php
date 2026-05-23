<?php

declare(strict_types=1);

namespace saso\installer;

use saso\framework\DIContainer;
use saso\framework\View;

final class SchemaDIContainer implements DIContainer
{
    private View $view;

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->view = new SchemaView();
    }

    public function flow(): View
    {
        return $this->view;
    }
}
