<?php
namespace saso\framework;

use saso\root\RootDIContainer;

final class Loader
{
    public function __construct(
        private string $matter,
        private string $action,
        private array $query,
        private array $post,
        private array $config,
        private bool $authed,
        private \DateTime $now,
    )
    {
    }
    public function flow(array $flow): View
    {
        $inside = self::insideFlow(
            $flow, $this->query, $this->post, $this->config, $this->now
        );    
        $DIContainer = new $flow[$this->matter][$this->action]();
        if($DIContainer->isTopLevel()) {
            $DIContainer->di($inside, $this->query, $this->post, $this->config, $this->now);
            return $DIContainer->flow();
        } else {
            $RootDIContainer = new RootDIContainer($this->matter, $this->action, $flow, $this->authed);
            $RootDIContainer->di($inside, $this->query, $this->post, $this->config, $this->now);
            return $RootDIContainer->flow();
        }
    }
    private static function insideFlow(array $flow, array $query, array $post, array $config, \DateTime $now): \Closure
    {
        return function($matter, $action, ...$args) use ($flow, $query, $post, $config, $now): View {
            $DIContainer = new $flow[$matter][$action](...$args);
            $DIContainer->di(self::insideFlow(
                $flow, $query, $post, $config, $now
            ), $query, $post, $config, $now);
            $view = $DIContainer->flow();
            if(!$view->onRoot()) {
                $view->display();
                $view->getContent()($view);
            }
            return $view;
        };
    }
}