<?php
namespace saso\search;

use saso\framework\Setter;
use saso\framework\View;

final class StartView implements View
{
    use Setter;
    private \Closure $content;
    private string $title;

    public function __construct(
        public readonly \Closure $inside,
        public string $search = ''
    ) {}

    public function display(): void
    {
        require_once 'search/template/start.php';
    }
    public function onRoot(): bool
    {
        return true;
    }
    public function getTitle(): string
    {
        return $this->title ?? 'Search Items';
    }
    public function getContent(): \Closure
    {
        return $this->content;
    }
}

