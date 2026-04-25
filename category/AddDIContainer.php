<?php
namespace saso\category;

use saso\common;
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
        return true;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->notPost = empty($post);
        $this->view = new common\FailJsonView();
        $this->ctrl = new IdController($post, new NameController($post));
        $this->usecase = new AddUsecase(
            new DbFinder(),
            new DbUpdater(),
            new DbTransaction(),
            new PostPresenter(
                new common\EmptyJsonView(),
                $this->view
            )
        );
    }
}
