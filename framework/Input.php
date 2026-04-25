<?php
namespace saso\framework;

trait Input
{
    public function input(Usecase $usecase): void
    {
        $usecase->handle($this->data);
    }
}