<?php
namespace saso\image;

use saso\entity\Archive;
use saso\entity\Color;
use saso\entity\Item;
use saso\framework\Setter;
use saso\framework\View;

final class StartView implements View
{
    use Setter;
    private string $title;
    private \Closure $content;
    private Archive $archive;
    private Item $item;
    private Color $color;

    public function __construct(
        private \Closure $inside,
    )
    {
    }
    public function display(): void
    {
        require_once 'image/template/start.php';
    }
    public function onRoot(): bool
    {
        return true;
    }
    public function getTitle(): string
    {
        return $this->title;
    }
    public function getContent(): \Closure
    {
        return $this->content;
    }
}
