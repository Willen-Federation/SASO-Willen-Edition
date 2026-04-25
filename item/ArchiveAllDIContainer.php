<?php
namespace saso\item;

use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DbFinder;

final class ArchiveAllDIContainer implements DIContainer
{
    use Flow;
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside , array $query, array $post, array $config, \DateTime $now): void
    {
        $this->ctrl = new ListContentsController($query, $config, false);
        $this->usecase = new ListContentsUsecase(
            new DbFinder(),
            new ListContentsPresenter(
                new ArchiveAllView(),
                new ListContentsEmptyView()
            ),
            $inside,
        );
    }
}
