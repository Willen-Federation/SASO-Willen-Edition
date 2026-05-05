<?php
namespace saso\role;

use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DbUpdater;

final class DeleteDIContainer implements DIContainer
{
    private DeleteUsecase $usecase;
    public function isTopLevel(): bool { return false; }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->usecase = new DeleteUsecase($post, new DbUpdater());
    }
    public function flow(): View { return $this->usecase->exec(); }
}
