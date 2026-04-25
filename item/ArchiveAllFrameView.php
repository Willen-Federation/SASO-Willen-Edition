<?php
namespace saso\item;

use saso\framework\Setter;
use saso\framework\View;

final class ArchiveAllFrameView implements View
{
    use Setter;
    private \Closure $content;
    private string $title;
    private string $searchUrl;
    private string $request = 'item/archivingAll';
    public function __construct(
        private \Closure $inside,
        private bool $isArchive,
        private string $search
    )
    {
        $this->searchUrl = $search === ''?'':'search/'.$this->search;
    }
    public function display(): void
    {
        require_once 'item/template/archiveAllFrame.php';
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
