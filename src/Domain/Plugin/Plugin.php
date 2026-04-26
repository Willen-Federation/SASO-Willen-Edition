<?php

declare(strict_types=1);

namespace Saso\Domain\Plugin;

/**
 * Contract every plugin class implements (cf. ADR 0015).
 *
 * Lifecycle:
 *   - `metadata()` — pure self-description, called during discovery
 *     (must NOT touch the database or any registries).
 *   - `register()` — invoked on every boot once the plugin row in
 *     `plugin_registry` shows it as active. Wires extension points
 *     (assistants, auth providers, MCP tools, event listeners,
 *     `/api/v1/plugins/*` routes) into the {@see PluginContext}.
 *   - `activate()` — invoked once when the plugin is first seen by
 *     the discovery loop, before the first `register()`. Use for
 *     one-shot work like seeding `system_setting` defaults or
 *     creating a vendor namespace under `system_setting`.
 *   - `deactivate()` — invoked when an operator disables the plugin
 *     in the admin UI. Use for cleanup (cache flush, token revoke);
 *     do NOT drop tables or remove `system_setting` rows the user
 *     may want to keep.
 *
 * Implementations should be defensive: a plugin must not assume
 * other plugins have already loaded, and must not throw from
 * `metadata()`.
 */
interface Plugin
{
    public function metadata(): PluginMetadata;

    public function register(PluginContext $context): void;

    public function activate(PluginContext $context): void;

    public function deactivate(PluginContext $context): void;
}
