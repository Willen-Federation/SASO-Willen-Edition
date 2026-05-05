<?php
namespace saso\label;

use saso\repository\DbFinder;
use saso\repository\DbTransaction;
use saso\repository\DbUpdater;
use saso\common;
use saso\framework\DIContainer;
use saso\framework\OnlyPostFlow;

final class DeleteAllDIContainer implements DIContainer
{
    use OnlyPostFlow;
    public function isTopLevel(): bool
    {
        return true;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->notPost = empty($post);
        $this->view = new common\FailView();
        $this->ctrl = new common\EmptyController();
        $this->usecase = new DeleteAllUsecase(
            new DbFinder(),
            new DbUpdater(),
            new DbTransaction(),
            new common\RedirectOrErrorPresenter(
                new common\RegisterSuccessView(),
                new common\RegisterFailView('label/features')
,            )
        );
    }
}
