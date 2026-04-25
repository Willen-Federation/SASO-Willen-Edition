<?php
namespace saso\item;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\OnlyPostFlow;
use saso\repository\DbFinder;
use saso\repository\DbTransaction;
use saso\repository\DbUpdater;
use saso\util\Verifier;

final class RegisterDIContainer implements DIContainer
{
    use OnlyPostFlow;
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->notPost = empty($post);
        $this->view = new RegisterView();
        $this->ctrl = new RegisterController($post, $now);
        if(!Verifier::verify($post)) {
            $this->usecase = new RegisterConfirmUsecase(
                new DbFinder(),
                new RegisterConfirmPresenter(
                    new RegisterConfirmView(),
                    new common\RegisterFailView('item/add'),
                ),
            );
        } else {
            $this->usecase = new RegisterUsecase(
                new DbFinder(),
                new DbUpdater(),
                new DbTransaction(),
                new common\RedirectOrErrorPresenter(
                    new common\RegisterSuccessView(),
                    new common\RegisterFailView('item/add'),
                ),
            );
        }
    }
}