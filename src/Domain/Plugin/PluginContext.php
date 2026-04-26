<?php

declare(strict_types=1);

namespace Saso\Domain\Plugin;

use Saso\Domain\Plugin\Registry\AiAssistantRegistry;
use Saso\Domain\Plugin\Registry\ApiRouteRegistry;
use Saso\Domain\Plugin\Registry\AuthProviderRegistry;
use Saso\Domain\Plugin\Registry\DomainEventBus;
use Saso\Domain\Plugin\Registry\McpToolRegistry;
use Saso\Domain\Setting\SystemSettingService;

/**
 * Facade handed to a {@see Plugin} during `register()` / `activate()`
 * / `deactivate()` (cf. ADR 0015).
 *
 * Plugins receive only the extension points they are allowed to
 * touch — the core PDO connection, HTTP kernel, container, and
 * filesystem are deliberately not exposed. A plugin that wants more
 * has to negotiate the surface area in a future ADR; the M6-J
 * surface is intentionally narrow.
 *
 * The facade is constructed once per request inside the boot loop
 * (M6-J4) and reused across every plugin in registration order.
 */
interface PluginContext
{
    public function aiAssistants(): AiAssistantRegistry;

    public function authProviders(): AuthProviderRegistry;

    public function mcpTools(): McpToolRegistry;

    public function domainEvents(): DomainEventBus;

    public function apiRoutes(): ApiRouteRegistry;

    public function systemSettings(): SystemSettingService;
}
