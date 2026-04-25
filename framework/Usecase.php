<?php
namespace saso\framework;

interface Usecase
{
    public function handle(DTO $data): void;
    public function output(): View;
}
