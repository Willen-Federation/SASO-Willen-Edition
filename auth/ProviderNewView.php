<?php

namespace saso\auth;

use saso\framework\Setter;
use saso\framework\View;

final class ProviderNewView implements View
{
    use Setter;

    private string $title;
    private \Closure $content;
    private string $errorMessage;

    public function display(): void
    {
        require_once 'auth/template/provider_new.php';
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
