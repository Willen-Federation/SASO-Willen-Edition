<?php
namespace saso\framework;

trait OutputForSingleEntity
{
    public function output(): View
    {
        return $this->presenter->complete($this->output);
    }
}