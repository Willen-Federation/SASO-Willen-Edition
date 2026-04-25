<?php
namespace saso\item;

use saso\entity\Item;
use saso\framework\Setter;
use saso\framework\View;

final class ChangeSizeOrderView implements View
{
    use Setter;
    private \Closure $content;
    private Item $item;
    private \Generator $sizes;
    public function display(): void
    {
        require_once 'item/template/changeSizeOrder.php';
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