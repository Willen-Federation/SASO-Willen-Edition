<?php
namespace saso\feature;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DbFinder;

final class LabelAmountFeaturesDIContainer implements DIContainer
{
    use Flow;
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->ctrl = new common\EmptyController();
        $this->usecase = new LabelAmountFeaturesUsecase(
            new DbFinder(),
            new FeaturesPresenter(
                new FeaturesView(),
                new common\EmptyView(),
            )
        );
    }
}