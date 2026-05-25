<?php

declare(strict_types=1);

namespace saso\admin;

use saso\framework\Setter;
use saso\framework\View;

final class NotificationsView implements View
{
    use Setter;

    private string $title;
    private \Closure $content;

    public bool $authorized     = false;
    public bool $sent           = false;
    public ?string $loadError   = null;
    public ?string $sendError   = null;
    public bool $fcmConfigured  = false;

    /** @var list<array<string, mixed>> */
    public array $history = [];

    public function display(): void
    {
        require_once 'admin/template/notifications.php';
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
