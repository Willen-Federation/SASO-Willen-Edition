<?php
namespace saso\item;

use saso\entity\Item;
use saso\entity\ItemVar;
use saso\framework\Setter;
use saso\framework\View;

final class ChangeMetaView implements View
{
    use Setter;
    private \Closure $content;
    private Item $item;
    private ItemVar $itemVar;
    public function display(): void
    {
        require_once 'item/template/changeMeta.php';
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
