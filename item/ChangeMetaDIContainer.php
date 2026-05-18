<?php
namespace saso\item;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DbFinder;
use saso\repository\DbTransaction;
use saso\repository\DbUpdater;

final class ChangeMetaDIContainer implements DIContainer
{
    use Flow;
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->ctrl = new ItemController($query, new ChangeMetaController($post, $now));
        if(empty($post)) {
            $this->usecase = new ItemVarUsecase(
                new DbFinder(),
                new ItemVarPresenter(
                    new ChangeMetaView(),
                    new common\FailView(),
                ),
            );
        } else {
            $this->usecase = new ChangeMetaUsecase(
                new DbFinder(),
                new DbUpdater(),
                new DbTransaction(),
                new common\RedirectOrErrorPresenter(
                    new common\RegisterSuccessView(),
                    new common\RegisterFailView('item/edit/item/'.$query['item']),
                ),
            );
        }
    }
}
