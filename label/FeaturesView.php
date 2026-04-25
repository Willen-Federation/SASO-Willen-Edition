<?php
namespace saso\label;

use saso\framework\Setter;
use saso\framework\View;

final class FeaturesView implements View
{
    use Setter;
    private string $title;
    private \Closure $content;
    private \Generator $labelCaches;
    public function __construct(
        private \Closure $inside,
    )
    {
    }
    public function display(): void
    {
        require_once 'label/template/features.php';
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

