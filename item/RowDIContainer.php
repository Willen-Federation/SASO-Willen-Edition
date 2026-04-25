<?php
namespace saso\item;

use saso\common;
use saso\entity;
use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DbFinder;

final class RowDIContainer implements DIContainer
{
    use Flow;
    public function __construct(
        private entity\Item $item,
    )
    {
    }
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->ctrl = new common\EmptyController();
        $this->usecase = new RowUsecase(
            $this->item,
            new DbFinder(),
            new RowPresenter(
                new RowView()
            )
        );
    }
}
