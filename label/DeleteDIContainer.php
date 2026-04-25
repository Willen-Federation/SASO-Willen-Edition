<?php
namespace saso\label;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\OnlyPostFlow;
use saso\repository\DbFinder;
use saso\repository\DbTransaction;
use saso\repository\DbUpdater;

final class DeleteDIContainer implements DIContainer
{
    use OnlyPostFlow;
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->notPost = empty($post);
        $this->view = new common\FailView();
        $this->ctrl =  new NameController($post);
        $this->usecase = new DeleteUsecase(
            new DbFinder(),
            new DbUpdater(),
            new DbTransaction(),
            new common\RedirectOrErrorPresenter(
                new common\RegisterSuccessView(),
                new common\RegisterFailView('label/start')
,            )
            );
    }
}
