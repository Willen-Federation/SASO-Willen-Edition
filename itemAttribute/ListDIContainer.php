<?php
namespace saso\itemAttribute;

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
        $this->view    = new common\FailJsonView();
        $this->ctrl    = new common\EmptyController();
        $this->usecase = new ListUsecase(
            new DbFinder(),
            new ListPresenter(
                new ListView(),
            )
        );
    }
}
