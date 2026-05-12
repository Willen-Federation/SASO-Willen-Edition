<?php
namespace saso\item;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DbFinder;
use saso\repository\DbTransaction;
use saso\repository\DbUpdater;

final class AttributeValuesDIContainer implements DIContainer
{
    use Flow;

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        if (empty($post)) {
            $this->ctrl    = new AttributeValuesController($query);
            $this->usecase = new AttributeValuesUsecase(
                new DbFinder(),
                new AttributeValuesPresenter(
                    new AttributeValuesView(),
                    new common\FailView(),
                )
            );
        } else {
            $this->ctrl    = new AttributeValuesController($query, $post);
            $this->usecase = new AttributeValuesSaveUsecase(
                new DbFinder(),
                new DbUpdater(),
                new DbTransaction(),
                new common\RedirectOrErrorPresenter(
                    new common\RegisterSuccessView(),
                    new common\RegisterFailView('item/edit/item/' . ($query['item'] ?? '')),
                )
            );
        }
    }
}
