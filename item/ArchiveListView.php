<?php
namespace saso\item;

use saso\framework\Setter;
use saso\framework\View;

final class ArchiveListView implements View
{
    use Setter;
    private string $title;
    private \Closure $content;
    private bool $isArchive = true;
    public function __construct(
        private \Closure $inside,
    )
    {
    }
    public function display(): void
    {
        require_once 'item/template/archiveList.php';
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
