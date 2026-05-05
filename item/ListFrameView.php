<?php
namespace saso\item;

use saso\framework\Setter;
use saso\framework\View;

final class ListFrameView implements View
{
    use Setter;
    private \Closure $content;
    private string $request;
    private string $searchUrl;
    public function __construct(
        private \Closure $inside,
        private bool $isArchive,
        private string $search
    )
    {
        $this->request = $this->isArchive?'archive/list':'start/start';
        $this->searchUrl = $search === ''?'':'search/'.$this->search;
    }
    public function display(): void
    {
        require_once 'item/template/listFrame.php';
    }
    public function onRoot(): bool
    {
        // Direct navigation to /item/listFrame/ must render inside Tabler
        // chrome. Returning false echoes the fragment before <!DOCTYPE> and
        // produces a whiteout. The template uses the standard
        // `$this->content = function($v) { ... }` pattern so it is fully
        // compatible with the wrapped path.
        return true;
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
