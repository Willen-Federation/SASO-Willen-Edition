<?php
namespace saso\label;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\OnlyPostFlow;
use saso\repository\DbFinder;

final class ShortNameDIContainer implements DIContainer
{
    use OnlyPostFlow;
    public function isTopLevel(): bool
    {
        return true;
    }
    public function di(\Closure $inside , array $query, array $post, array $config, \DateTime $now): void
    {
        $this->notPost = empty($post);
        $this->view = new common\FailEmptyView();
        $this->ctrl = new NameController($post, new ShortNameController($post));
        $this->usecase = new ShortNameUsecase(
            new DbFinder(),
            new ShortNamePresenter(
                new ShortNameView(),
                $this->view,
            )
        );
    }
}


