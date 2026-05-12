<?php
namespace saso\item;

use saso\framework\Setter;
use saso\framework\View;

final class BulkImportResultView implements View
{
    use Setter;

    private string $title;
    private \Closure $content;
    private array $results      = [];
    private int $successCount   = 0;
    private int $errorCount     = 0;

    public function display(): void
    {
        require_once 'item/template/bulkImportResult.php';
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
