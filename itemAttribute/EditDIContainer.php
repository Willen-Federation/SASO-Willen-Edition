<?php
namespace saso\itemAttribute;

use saso\common\EmptyUsecase;
use saso\common\EmptyPresenter;
use saso\framework\DIContainer;
use saso\framework\Flow;

final class EditDIContainer implements DIContainer
{
    use Flow;

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->ctrl    = new \saso\common\EmptyController();
        $this->usecase = new EmptyUsecase(
            new EmptyPresenter(
                new EditView(),
            )
        );
    }
}
