<?php
namespace saso\shelf;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\Flow;

final class SingleDIContainer implements DIContainer
{
    use Flow;
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->ctrl = new SingleController($query);
        $this->usecase = new ShelvesUsecase(
            new SinglePresenter(
                new ListView($inside),
                new common\RegisterFailView('shelf/start'),
            )
        );
    }
}
