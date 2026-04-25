<?php
namespace saso\shelf;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\OnlyPostFlow;

final class ShelvesDIContainer implements DIContainer
{
    use OnlyPostFlow;
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->notPost = empty($post);
        $this->view = new common\EmptyView();
        $this->ctrl = new ShelvesController($post);
        $this->usecase = new ShelvesUsecase(
            new ShelvesPresenter(
                new ShelvesView(),
                $this->view,
            )
        );
    }
}