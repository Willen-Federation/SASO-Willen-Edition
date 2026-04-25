<?php
namespace saso\item;

use saso\entity\Item;
use saso\framework\Setter;
use saso\framework\View;

final class AddFeatureConfirmView implements View
{
    use Setter;
    private string $title;
    private \Closure $content;
    private Item $item;
    private string $serializedColors;
    private string $serializedSizes;
    private string $inputColors;
    private string $inputSizes;
    private bool $isValidAmount;
    public function __construct(
        private \Closure $inside,
    )
    {
    }
    public function display(): void
    {
        require_once 'item/template/addFeatrueConfirm.php';
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
