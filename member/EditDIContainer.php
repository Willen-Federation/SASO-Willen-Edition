<?php
namespace saso\member;

use saso\framework\DIContainer;
use saso\framework\View;
use saso\repository\DbUpdater;
use saso\repository\DbFinder;
use saso\repository\DBConnection;
use Saso\Application\Auth\AdminGuard;

final class EditDIContainer implements DIContainer
{
    private $usecase;
    public function isTopLevel(): bool { return false; }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->usecase = new EditUsecase(
            $query,
            $post,
            new DbFinder(),
            new DbUpdater(),
            new EditPresenter(new EditView()),
            new AdminGuard(DBConnection::getPdo()),
        );
    }
    public function flow(): View
    {
        return $this->usecase->exec();
    }
}
