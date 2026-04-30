<?php
namespace saso\item;

use saso\framework\Setter;
use saso\framework\View;

final class AddFromImageView implements View
{
    use Setter;
    private string $title;
    private \Closure $content;

    public function display(): void
    {
        require_once 'item/template/addFromImage.php';
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
