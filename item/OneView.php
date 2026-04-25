<?php
namespace saso\item;

use saso\entity\Archive;
use saso\entity\Item;
use saso\framework\Setter;
use saso\framework\View;

final class OneView implements View
{
    use Setter;
    private string $title;
    private \Closure $content;
    private Item $item;
    private Archive $archive;
    private \Generator $quantityLogsGen;
    private int $labelSheetsAmount;
    private int $labelSheetsAmountMax;
    private string $color;
    private string $size;
    private string $action;
    public function __construct(
        private \Closure $inside,
    )
    {
    }
    public function display(): void
    {
        require_once 'item/template/one.php';
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
