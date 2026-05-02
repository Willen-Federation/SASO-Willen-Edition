<?php
namespace saso\featureAdmin;

use saso\common\EmptyUsecase;
use saso\framework\DIContainer;
use saso\framework\Flow;

final class ListDIContainer implements DIContainer
{
    use Flow;

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->ctrl    = new ListController();
        $this->usecase = new EmptyUsecase(
            new ListPresenter(
                new ListView(),
            ),
        );
    }
}
