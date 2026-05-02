<?php

namespace saso\auth;

use saso\framework\Setter;
use saso\framework\View;

final class ProviderTestView implements View
{
    use Setter;

    private bool $ok = false;
    private string $message = '';
    private ?array $details = null;

    public function display(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($this->ok ? 200 : 502);
        
        echo json_encode(array_filter([
            'ok'      => $this->ok,
            'message' => $this->message,
            'details' => $this->details,
        ], static fn ($v): bool => $v !== null));
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
        return fn() => null;
    }
}
