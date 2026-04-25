<?php
namespace saso\item;

use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DbFinder;

final class ListContentsDIContainer implements DIContainer
{
    use Flow;
    public function __construct(
        private bool $isArchive,
    )
    {
    }
    public function isTopLevel(): bool
    {
        return false;
    }
    public function di(\Closure $inside , array $query, array $post, array $config, \DateTime $now): void
    {
        $this->ctrl = new ListContentsController($query, $config, $this->isArchive);
        $this->usecase = new ListContentsUsecase(
            new DbFinder(),
            new ListContentsPresenter(
                new ListContentsView(),
                new ListContentsEmptyView()
            ),
            $inside,
        );
    }
}
