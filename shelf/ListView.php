<?php
namespace saso\shelf;

use saso\framework\Setter;
use saso\framework\View;

final class ListView implements View
{
    use Setter;
    private int $pagesAmount;
    private array $shelves;
    private int $page;
    private array $mins;
    private array $maxs;
    private string $title;
    private \Closure $content;
    public function __construct(
        private \Closure $inside,
    )
    {
    }
    public function display(): void
    {
        require_once 'shelf/template/list.php';
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
