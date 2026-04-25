<?php
namespace saso\item;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DbFinder;
use saso\repository\DbTransaction;
use saso\repository\DbUpdater;
use saso\util\Verifier;

final class AddFeatureDIContainer implements DIContainer
{
    use Flow;
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->ctrl = new ItemController($query, new AddFeatureController($post, $now));
        if(empty($post)) {
            $this->usecase = new ItemUsecase(
                new DbFinder(),
                new ItemPresenter(
                    new AddFeatureView($inside),
                    new common\FailView(),
                )
            );
        } else if(!Verifier::verify($post)) {
            $this->usecase = new AddFeatureConfirmUsecase(
                new DbFinder(),
                new AddFeaturePresenter(
                    new AddFeatureConfirmView($inside),
                    new common\FailView(),
                )
            );
        } else {
            $this->usecase = new AddFeatureUsecase(
                new DbFinder(),
                new DbUpdater(),
                new DbTransaction(),
                new common\RedirectOrErrorPresenter(
                    new common\RegisterSuccessView(),
                    new common\RegisterFailView('item/addFeature/item/'.$query['item']),
                ),
            );
        }
    }
}