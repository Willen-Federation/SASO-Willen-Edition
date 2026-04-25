<?php
namespace saso\label;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\OnlyPostFlow;
use saso\repository\DbFinder;

final class SizeDIContainer implements DIContainer
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
        $this->ctrl = new NameController($post);
        $this->usecase = new SizeUsecase(
            new DbFinder(),
            new SizePresenter(
                new SizeView(),
                new common\EmptyJsonView(),
            )
        );
    }
}
