<?php
namespace saso\common;

use saso\framework\Setter;
use saso\framework\View;

final class FailJsonView implements View
{
    use Setter;
    private string $errorMessage = '';
    private int $status = 400;
    private string $errorCode = 'SASO-INFRA-9000';

    public function display(): void
    {
        header('Content-Type: application/problem+json; charset=utf-8');
        
        $statusMsg = match($this->status) {
            400 => '400 Bad Request',
            401 => '401 Unauthorized',
            403 => '403 Forbidden',
            404 => '404 Not Found',
            405 => '405 Method Not Allowed',
            default => '500 Internal Server Error',
        };
        header("HTTP/1.1 " . $statusMsg);

        echo json_encode([
            'type'     => 'https://docs.willen-federation.org/error-codes#' . $this->errorCode,
            'title'    => $this->errorMessage ?: 'Error',
            'status'   => $this->status,
            'detail'   => $this->errorMessage,
            'instance' => $_SERVER['REQUEST_URI'] ?? '',
            'code'     => $this->errorCode,
            'traceId'  => bin2hex(random_bytes(16)),
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