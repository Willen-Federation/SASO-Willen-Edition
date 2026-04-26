<?php

declare(strict_types=1);

namespace Saso\Domain\Plugin;

/**
 * Marker interface every plugin class implements (cf. ADR 0015).
 *
 * The full lifecycle (`metadata` / `register` / `activate` /
 * `deactivate`) and the `PluginContext` facade with its six typed
 * registries land alongside the discovery loop in M6-J2. This PR
 * ships the storage tier — the migration + record + repository —
 * so the M6-J2 implementation can land against a stable contract.
 *
 * Plugins that want to surface ahead of M6-J2 should implement only
 * {@see metadata()}; the discovery loop tolerates a missing
 * `register()` and logs a `notice` until the full interface
 * extension lands.
 */
interface Plugin
{
    public function metadata(): PluginMetadata;
}
