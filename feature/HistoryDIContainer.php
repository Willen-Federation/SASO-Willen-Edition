<?php
namespace saso\feature;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DbFinder;

final class HistoryDIContainer implements DIContainer
{
    use Flow;
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->ctrl = new FeatureController($query);
        $this->usecase = new HistoryUsecase(
            new DbFinder(),
            new HistoryPresenter(
                new HistoryView($inside),
                new common\FailView(),
            )
        );
    }
}