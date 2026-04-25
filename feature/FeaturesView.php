<?php
namespace saso\feature;

use saso\framework\Setter;
use saso\framework\View;

final class FeaturesView implements View
{
    use Setter;
    private \Generator $features;
    public function display(): void
    {
    }
    public function onRoot(): bool
    {
        return true;
    }
    public function getTitle(): string
    {
        return '';
    }
    public function getContent(): \Closure
    {
        return fn()=>$this->features;
    }
}