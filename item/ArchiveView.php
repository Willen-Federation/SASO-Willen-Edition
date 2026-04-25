<?php
namespace saso\item;

use saso\entity\Item;
use saso\framework\Setter;
use saso\framework\View;

final class ArchiveView implements View
{
    use Setter;
    private \Closure $content;
    private Item $item;
    public function display(): void
    {
        require_once 'item/template/archive.php';
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
        return $this->content;
    }
}
