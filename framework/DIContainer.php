<?php
namespace saso\framework;

interface DIContainer
{
    public function isTopLevel(): bool;
    public function di(
        \Closure $inside,
        array $query,
        array $post,
        array $config,
        \DateTime $now,
    ): void;
    public function flow(): View;
}