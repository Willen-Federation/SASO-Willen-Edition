<?php
namespace saso\item;

use saso\framework\Setter;
use saso\framework\View;

final class ListContentsView implements View
{
    use Setter;
    private \Closure $content;
    private array $insides;
    private string $request = 'start/start';
    public function display(): void
    {
        require_once 'item/template/listContents.php';
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
