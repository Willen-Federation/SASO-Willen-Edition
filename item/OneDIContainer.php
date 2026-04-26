<?php
namespace saso\item;

use saso\common;
use saso\feature\FeatureController;
use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DbFinder;

final class OneDIContainer implements DIContainer
{
    use Flow;
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside , array $query, array $post, array $config, \DateTime $now): void
    {
        $this->ctrl = new FeatureController($query, new OneController($query, $config));
        $this->usecase = new OneUsecase(
            new DbFinder(),
            new OnePresenter(
                new OneView($inside),
                new common\FailView(),
            )
        );
    }
}
