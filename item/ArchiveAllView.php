<?php
namespace saso\item;

use saso\framework\Setter;
use saso\framework\View;

final class ArchiveAllView implements View
{
    use Setter;
    private \Closure $content;
    private array $insides;
    public function display(): void
    {
        require_once 'item/template/archiveAll.php';
    }
    public function onRoot(): bool
    {
        return false;
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
