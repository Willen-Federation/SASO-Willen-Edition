<?php
namespace saso\image;

use saso\common;
use saso\common\RedirectOrErrorPresenter;
use saso\feature\FeatureController;
use saso\framework\DIContainer;
use saso\framework\OnlyPostFlow;
use saso\repository\DbFinder;
use saso\repository\DbTransaction;
use saso\repository\DbUpdater;

final class AddDIContainer implements DIContainer
{
    use OnlyPostFlow;
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->notPost = $_FILES['image']['tmp_name']?false:true;
        $this->view = new common\FailView();
        $this->ctrl = new FeatureController($query, new AddController($_FILES['image']));
        $this->usecase = new AddUsecase(
            new DbFinder(),
            new DbUpdater(),
            new DbTransaction(),
            new RedirectOrErrorPresenter(
                new common\RegisterSuccessView(),
                new common\RegisterFailView('./image/start/item/'.$query['item'].'/color/'.$query['color']),
            )
            );
    }
}
