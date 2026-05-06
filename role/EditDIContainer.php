<?php
namespace saso\role;

use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DbFinder;
use saso\repository\DbUpdater;

final class EditDIContainer implements DIContainer
{
    private EditUsecase $usecase;
    public function isTopLevel(): bool { return false; }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->usecase = new EditUsecase(
            $query,
            $post,
            new DbFinder(),
            new DbUpdater(),
            new EditPresenter(new EditView()),
        );
    }
    public function flow(): View { return $this->usecase->exec(); }
}
