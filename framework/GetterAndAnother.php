<?php
namespace saso\framework;

trait GetterAndAnother
{
    public function __get($prop)
    {
        return $this->$prop??$this->another->$prop;
    }
}