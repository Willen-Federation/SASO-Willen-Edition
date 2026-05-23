<?php

declare(strict_types=1);

namespace saso\admin;

use saso\framework\Setter;
use saso\framework\View;

/**
 * Admin console page that lets administrators edit values that
 * historically only lived in `.env` — DB credentials (read-only when
 * sourced from the real OS environment), security toggles, auth
 * provider keys, and the bootstrap seed credentials.
 *
 * Writes route to either `.env` or `system_setting`:
 *   * Anything that must be available before the DB connects (DB
 *     credentials, APP_KEY) stays in `.env`.
 *   * Auth0 / Firebase / SEED_* values can also live in
 *     `system_setting` so they survive `.env` rotation.
 */
final class EnvSettingsView implements View
{
    use Setter;

    private string $title = 'ENV 設定';
    private \Closure $content;

    public bool $authorized = false;
    public bool $saved = false;
    public ?string $loadError = null;
    public ?string $writeError = null;

    /** @var array<string, mixed> */
    public array $env = [];

    /** @var array<string, mixed> */
    public array $settings = [];

    public bool $envWritable = false;
    public string $envPath   = '';

    public function display(): void
    {
        require_once 'admin/template/env-settings.php';
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
        return $this->content ?? fn () => null;
    }
}
