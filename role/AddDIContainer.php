<?php
namespace saso\role;

use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DbUpdater;

final class AddDIContainer implements DIContainer
{
    private AddUsecase $usecase;
    public function isTopLevel(): bool { return false; }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->usecase = new AddUsecase(
            $post,
            new DbUpdater(),
            new AddPresenter(new AddView()),
        );
    }
    public function flow(): View { return $this->usecase->exec(); }
}
