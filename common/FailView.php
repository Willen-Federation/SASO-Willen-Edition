<?php
namespace saso\common;

use saso\framework\Setter;
use saso\framework\View;

final class FailView implements View
{
    use Setter;
    private string $title;
    private \Closure $content;
    public function display(): void
    {
        header("HTTP/1.1 404 Not Found");
        require_once 'common/template/notFound.php';
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
