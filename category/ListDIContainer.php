<?php
namespace saso\category;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\OnlyPostFlow;
use saso\repository\DbFinder;

final class ListDIContainer implements DIContainer
{
    use OnlyPostFlow;
    public function isTopLevel(): bool
    {
        return true;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->notPost = empty($post);
        $this->view = new common\FailEmptyView();
        $this->ctrl = new IdController($post);
        $this->usecase = new ListUsecase(
            new DbFinder(),
            new ListPresenter(
                new ListView(),
            )
        );
    }
}