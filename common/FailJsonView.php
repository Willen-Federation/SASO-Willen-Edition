<?php
namespace saso\common;

use saso\framework\Setter;
use saso\framework\View;

final class FailJsonView implements View
{
    use Setter;
    private string $errorMessage='';
    public function display(): void
    {
        header('Content-Type: application/json');
        header("HTTP/1.1 400 Bad Request");
        echo json_encode([
            'errorMessage'=>$this->errorMessage,
        ]);
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
        return fn()=>null;
    }
}