<?php
namespace saso\item;

use saso\entity;
use saso\framework\Setter;
use saso\framework\View;

final class RowView implements View
{
    use Setter;
    private \Closure $content;
    private entity\Item $item;
    private entity\ItemVar $iv;
    private \Generator $colors;
    private \Generator $sizes;
    public function display(): void
    {
        require 'item/template/row.php';
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
