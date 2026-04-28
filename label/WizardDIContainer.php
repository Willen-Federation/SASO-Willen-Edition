<?php
namespace saso\label;

use saso\common\EmptyUsecase;
use saso\framework\DIContainer;
use saso\framework\Flow;

final class WizardDIContainer implements DIContainer
{
    use Flow;

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->ctrl    = new WizardController();
        $this->usecase = new EmptyUsecase(
            new WizardPresenter(
                new WizardView(),
            ),
        );
    }
}
