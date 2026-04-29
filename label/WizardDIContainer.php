<?php
namespace saso\label;

use saso\common\EmptyController;
use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DbFinder;

final class WizardDIContainer implements DIContainer
{
    use Flow;

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->ctrl    = new EmptyController();
        $this->usecase = new WizardUsecase(
            new DbFinder(),
            new WizardPresenter(
                new WizardView(),
            ),
        );
    }
}
