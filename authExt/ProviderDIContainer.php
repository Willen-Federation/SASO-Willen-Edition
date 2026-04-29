<?php
namespace saso\authExt;

use saso\common\EmptyUsecase;
use saso\framework\DIContainer;
use saso\framework\Flow;

final class ProviderDIContainer implements DIContainer
{
    use Flow;

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->ctrl    = new ProviderController($query, $post);
        $this->usecase = new EmptyUsecase(
            new ProviderPresenter(
                new ProviderView($query, $post),
            ),
        );
    }
}
