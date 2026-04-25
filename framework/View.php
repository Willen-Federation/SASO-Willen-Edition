<?php
namespace saso\framework;

interface View 
{
    public function __call(string $name, array $args);
    public function display(): void;
    public function onRoot(): bool;
    public function getTitle(): string;
    public function getContent(): \Closure;
}
