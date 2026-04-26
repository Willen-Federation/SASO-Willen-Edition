# 0015 — Plugin system: Composer-installed packages with extension points

* Status: accepted
* Date: 2026-04-26
* Deciders: @kackey621, Willen Federation contributors

## Context and Problem Statement

Operators want to extend SASO without forking the project — add an ERP integration, swap the label-printing PDF engine, plug in an extra AI provider, or wire a domain-specific notification channel. Today the only way to add behaviour is to edit `src/` and rebuild the release ZIP.

We need a plugin system that:

1. lets third parties ship code as a Composer package and have SASO discover it on install;
2. exposes a small, stable set of **extension points** — adding a route, hooking an item-write event, registering an `AuthProvider` (ADR 0003) / `AiAssistant` (ADR 0009) / `McpTool` (ADR 0014);
3. respects the lockout-safety story — `SAFE_MODE=true` disables every plugin;
4. has a clear lifecycle (install → activate → upgrade → deactivate → uninstall) operators can audit;
5. composes with the Strangler Fig migration (ADR 0001) — plugins live in their own namespace and never edit legacy code in place.

## Decision Drivers

- **Composer-native** — operators already run `composer install`. Plugins should ship as `composer require willen-federation/saso-plugin-foo`.
- **Schema-aware** — plugins that need DB tables provide their own Phinx migrations (ADR 0007).
- **Discoverable, not magical** — `composer.json` declares the plugin's class via the `extra` block; SASO reads it. No filesystem scans, no auto-registered hooks.
- **Zero plugin → zero overhead** — when no plugins are installed, the boot path is unchanged.
- **Compliance with ADR 0001** — extension-point interfaces live under `src/Domain/Plugin/` and adapter glue under `src/Infrastructure/Plugin/`.

## Considered Options

### Option A — In-repo `extension/` directory operators drop files into

* (+) Zero packaging.
* (−) Operators run `git pull` and lose their plugin. No version pinning, no dependency resolution.

### Option B — A bespoke plugin loader with a custom registry file

* (+) Decoupled from Composer.
* (−) Reinvents version pinning, autoload, security advisory scanning. Composer already does these.

### Option C — Composer-discovered plugins via `composer.json` `extra.saso.plugin`

Plugins ship as Composer packages whose `composer.json` carries:

```json
"extra": {
    "saso": {
        "plugin": {
            "class": "Acme\\\\SasoLabelExtras\\\\Plugin",
            "name": "Label extras",
            "version-compat": "^1.0"
        }
    }
}
```

`Saso\Domain\Plugin\Plugin` is the lifecycle interface every plugin class implements. SASO walks `vendor/composer/installed.json` once at boot, instantiates each `Plugin`, and asks it to `register()` against a `PluginContext` that exposes the application's typed registries:

```php
interface Plugin
{
    public function metadata(): PluginMetadata;
    public function register(PluginContext $ctx): void;
    public function activate(PluginContext $ctx): void;     // first install
    public function deactivate(PluginContext $ctx): void;   // disable / uninstall
}

interface PluginContext
{
    public function authProviders(): AuthProviderRegistry;
    public function aiAssistants(): AiAssistantRegistry;
    public function mcpTools(): McpToolRegistry;
    public function eventBus(): DomainEventBus;
    public function routes(): ApiRouteRegistry;
    public function settings(): SystemSettingService;
}
```

Plugins extend exactly the surfaces the registries expose. They cannot edit core code or the legacy directory.

* (+) Composer handles dependencies, autoload, version pinning, and security advisories.
* (+) Operators see plugins via `composer show` and audit them via `composer audit`.
* (+) Discovery is one-shot at boot — no runtime filesystem scan.
* (+) The contract surface is small (six registries) — easy to keep stable across major releases.

## Decision Outcome

**Chosen option: C — Composer-discovered plugins with typed registries.**

### Lifecycle

1. **Install** — `composer require <plugin-package>`. Composer pulls the package into `vendor/`.
2. **Discover** — first request after install: `Saso\Infrastructure\Plugin\PluginDiscovery` reads `vendor/composer/installed.json`, finds entries with `extra.saso.plugin`, and instantiates each plugin class.
3. **Activate** — first time a plugin is seen, the loader calls `Plugin::activate($ctx)` and writes a row to `plugin_registry` (`package`, `class`, `version`, `activated_at`). Idempotent — re-running yields no work.
4. **Register** — every boot calls `Plugin::register($ctx)`, which the plugin uses to add itself to the relevant registries.
5. **Migrations** — if the plugin's package ships `migrations/` directory, the Phinx config (ADR 0007) picks them up via the dynamic-paths loader (M6-D2 work).
6. **Deactivate** — `composer remove <package>` triggers a one-shot `Plugin::deactivate($ctx)` from the next admin-UI session, which writes `deactivated_at` to `plugin_registry`. Plugin code stops loading.
7. **Uninstall** — Composer removes `vendor/<package>`. The `plugin_registry` row remains so audit history survives.

### Storage

```sql
CREATE TABLE plugin_registry (
    id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    package         VARCHAR(200) NOT NULL UNIQUE,
    class           VARCHAR(200) NOT NULL,
    name            VARCHAR(120) NOT NULL,
    version         VARCHAR(40) NOT NULL,
    activated_at    DATETIME NOT NULL,
    deactivated_at  DATETIME NULL,
    last_seen_at    DATETIME NULL,
    settings_json   JSON NULL,                  -- per-plugin config blob
    KEY idx_active (deactivated_at)
);
```

### Lockout safety

- `SAFE_MODE=true` skips every plugin's `register()` and `activate()`. Core SASO boots without third-party code.
- A plugin that throws from its constructor or `register()` is logged at `error` level (Monolog), recorded in `plugin_registry.last_seen_at = NULL`, and skipped — one bad plugin must not brick the instance.

### Permissions / scopes

Every registry has `add` / `remove` / `replace` semantics. Plugins cannot replace core providers (e.g. `LocalProvider`) — the registry rejects collisions on canonical names. They can add new providers, new tools, new routes.

### Compatibility

- `extra.saso.plugin.version-compat` is a Composer-style constraint against the SASO core version. The discovery step refuses to load a plugin whose constraint excludes the running version, and surfaces the mismatch in the admin UI.
- Major SASO bumps may break the plugin contract. Each major release ships a migration guide.

### Distribution

- A reference plugin scaffold (`willen-federation/saso-plugin-skeleton`) ships in M6 documentation. Operators clone, rename, edit, and `composer publish`.
- The first-party AI/Search/MCP capabilities stay in core; they are not plugins. The plugin surface is for third-party additions.

## Consequences

- Operators install plugins with a single Composer command. Removing them is the inverse.
- The plugin contract is small enough that core code reviewers can keep the surface honest (six registries; refusal to expose internals).
- `plugin_registry` gives operators a queryable answer to "what plugins are installed and active?".
- We accept a one-time read of `vendor/composer/installed.json` per request, mitigated by request-scoped caching of the resolved plugin list.
- Plugins that need migrations get them auto-loaded via the M6-D2 Phinx-config dynamic paths. Until that lands (early M6), plugin authors must run their migrations by hand and document the manual step.
