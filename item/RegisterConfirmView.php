<?php
namespace saso\item;

use saso\entity\ItemVar;
use saso\entity\Item;
use saso\framework\Setter;
use saso\framework\View;

final class RegisterConfirmView implements View
{
    use Setter;
    private string $title;
    private \Closure $content;
    private Item $item;
    private ItemVar $itemVar;
    private string $serializedColors;
    private string $serializedSizes;
    private string $inputColors;
    private string $inputSizes;
    private bool $validFeaturesAmount;
    public function display(): void
    {
        require_once 'item/template/registerConfirm.php';
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
