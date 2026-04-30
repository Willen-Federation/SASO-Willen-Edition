<?php
namespace saso\item;

use saso\framework\Setter;
use saso\framework\View;

final class DraftListView implements View
{
    use Setter;
    private string $title;
    private \Closure $content;
    public array $drafts = [];

    public function display(): void
    {
        require_once 'item/template/draftList.php';
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
