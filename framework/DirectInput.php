<?php
namespace saso\framework;

trait DirectInput
{
    public function input(Usecase $usecase): void
    {
        $usecase->handle($this);
    }
}