<?php
namespace saso\role;

use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DbFinder;

final class StartDIContainer implements DIContainer
{
    private StartUsecase $usecase;
    public function isTopLevel(): bool { return false; }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->usecase = new StartUsecase(
            new DbFinder(),
            new StartPresenter(new StartView()),
        );
    }
    public function flow(): View { return $this->usecase->exec(); }
}
