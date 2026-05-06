<?php
namespace saso\common;

use saso\framework\Setter;
use saso\framework\View;

final class FailView implements View
{
    use Setter;
    private string $title;
    private \Closure $content;
    private int $status = 404;

    public function display(): void
    {
        $statusMsg = match($this->status) {
            400 => '400 Bad Request',
            401 => '401 Unauthorized',
            403 => '403 Forbidden',
            404 => '404 Not Found',
            405 => '405 Method Not Allowed',
            default => '500 Internal Server Error',
        };
        header("HTTP/1.1 " . $statusMsg);
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
