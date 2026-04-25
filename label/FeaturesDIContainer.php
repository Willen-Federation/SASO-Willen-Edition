<?php
namespace saso\label;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DbFinder;

final class FeaturesDIContainer implements DIContainer
{
    use Flow;
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside , array $query, array $post, array $config, \DateTime $now): void
    {
        $this->ctrl = new common\EmptyController();
        $this->usecase = new FeaturesUsecase(
            new DbFinder(),
            new FeaturesPresenter(
                new FeaturesView($inside),
            )
        );
    }
}
