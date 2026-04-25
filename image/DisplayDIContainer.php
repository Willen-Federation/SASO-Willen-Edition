<?php
namespace saso\image;

use saso\common;
use saso\feature\FeatureController;
use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DbFinder;

final class DisplayDIContainer implements DIContainer
{
    use Flow;
    public function isTopLevel(): bool
    {
        return true;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->ctrl = new FeatureController($query);
        $this->usecase = new DisplayUsecase(
            new DbFinder(),
            new DisplayPresenter(
                new DisplayView(),
                new common\FailView(),
            )
        );
    }
}
