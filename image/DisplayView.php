<?php
namespace saso\image;

use saso\framework\Setter;
use saso\framework\View;

final class DisplayView implements View
{
    use Setter;
    private array $image;
    public function display(): void
    {
        header("Content-Type:". $this->image['color']->imageType);
        echo $this->image['image'];
    }
    public function onRoot(): bool
    {
        return false;
    }
    public function getTitle(): string
    {
        return '';
    }
    public function getContent(): \Closure
    {
        return fn()=>null;
    }
}
