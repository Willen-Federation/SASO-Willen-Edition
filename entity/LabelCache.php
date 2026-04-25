<?php
namespace saso\entity;

final class LabelCache
{
    public function __construct(
        private Feature $feature,
        private int $amount,
    )
    {
        $this->feature->setLabelAmount($this);
    }
    public function __get($name)
    {
        return $this->$name;
    }
}