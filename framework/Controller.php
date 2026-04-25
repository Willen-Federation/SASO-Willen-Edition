<?php
namespace saso\framework;

interface Controller
{
    public function input(Usecase $usecase): void;
}
