<?php
namespace saso\item;

use saso\framework\Setter;
use saso\framework\View;

final class DraftConfirmView implements View
{
    use Setter;
    private string $title;
    private \Closure $content;
    public array $draft = [];

    public function display(): void
    {
        require_once 'item/template/draftConfirm.php';
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
