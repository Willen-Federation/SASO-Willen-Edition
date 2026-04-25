<?php
namespace saso\js;

use saso\framework\DIContainer;
use saso\framework\View;
use saso\util\CSRFtoken;

final class JSDIContainer implements DIContainer
{
    private View $view;
    public function isTopLevel(): bool
    {
        return true;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->view = new JSView(
            $query['action'],
            CSRFtoken::salting($config['csrftokensalt'])
        );
    }
    public function flow(): View
    {
        return $this->view;
    }
}
