<?php
namespace saso\root;

use saso\framework\DIContainer;
use saso\framework\Flow;

final class RootDIContainer implements DIContainer
{
    use Flow;
    public function __construct(
        private string $matter,
        private String $action,
        private array $flow,
        private bool $authed,
    )
    {
    }
    public function isTopLevel(): bool
    {
        return true;
    }
    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $this->ctrl = new RootController($config, $this->authed, $this->matter, $this->action);
        $this->usecase = new RootUsecase(
            new RootPresenter(
                new RootView($inside),
            ),
        );
    }
}
