<?php

namespace saso\item;

use saso\framework\Setter;
use saso\framework\View;

final class BulkAddView implements View
{
    use Setter;
    private string $title;
    private \Closure $content;
    public string $step = 'upload';
    public string $token = '';
    public array $validRows = [];
    public array $errorRows = [];
    public ?string $flashSuccess = null;
    public ?string $flashError = null;

    public function display(): void
    {
        require_once 'item/template/bulkAdd.php';
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
