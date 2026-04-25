<?php
namespace saso\item;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DbFinder;

final class EditDIContainer implements DIContainer
{
    use Flow;
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside , array $query, array $post, array $config, \DateTime $now): void
    {
        $this->ctrl = new ItemController($query);
        $this->usecase = new EditUsecase(
            new DbFinder(),
            new EditPresenter(
                new EditView($inside),
                new common\FailView()
            )
        );
    }
}
