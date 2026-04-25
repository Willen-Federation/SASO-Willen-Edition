<?php
namespace saso\category;

use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DbFinder;

final class PathDIContainer implements DIContainer
{
    use Flow;
    public function isTopLevel(): bool
    {
        return true;
    }
    public function di(\Closure $inside , array $query, array $post, array $config, \DateTime $now): void
    {
        $this->ctrl = new IdController($query);
        $this->usecase = new PathUsecase(
            new DbFinder(),
            new PathPresenter(
                new PathView()
            ),
        );
    }
}
