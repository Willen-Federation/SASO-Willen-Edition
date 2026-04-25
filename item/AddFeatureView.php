<?php
namespace saso\item;

use saso\entity\Item;
use saso\framework\Setter;
use saso\framework\View;

final class AddFeatureView implements View
{
    use Setter;
    private string $title;
    private \Closure $content;
    private Item $item;
    public function __construct(
        private \Closure $inside,
    )
    {
    }
    public function display(): void
    {
        require_once 'item/template/addFeature.php';
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
