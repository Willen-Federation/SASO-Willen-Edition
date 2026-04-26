# 0005 — OpenFeature SDK + DB-backed provider with cron circuit breaker

* Status: accepted
* Date: 2026-04-26
* Deciders: @kackey621, Willen Federation contributors

## Context and Problem Statement

Several M3-M5 features ship behind controlled rollouts:

* OIDC / SAML providers (M4) — operators may want to enable Auth0 for staff before letting external partners in.
* Vendor-bundled release ZIP installer flow (M5) — a wrong-step changes the entire deploy story for shared-hosting users.
* Domain rewrites under the Strangler Fig migration — the new code path for "create item" can cohabit with the legacy path for a release before the legacy path is removed.
* Tracking experiments and gradual percentage rollouts as the system grows.

We need a runtime switching mechanism that:

1. lets operators flip a feature **without** deploying code or restarting Apache;
2. **auto-disables** a feature when its error rate crosses a configured threshold (so a bad rollout self-heals);
3. exposes a clean API to call sites — typed flag keys, not hard-coded `if (config.foo)` branches; and
4. works on shared hosting where cron may or may not be available.

## Decision Drivers

* **Operator-facing UI** — flips happen from the admin console (M4), not by editing config files.
* **Audit trail** — every flip is logged with who/when/why. "We just turned off X to recover from an incident" must be reconstructable from the database.
* **Self-healing on errors** — a flag whose code path is throwing should disable itself before an operator notices, then alert.
* **Cron-optional** — shared hosting that lacks cron must still get circuit-breaker behaviour, even at reduced freshness.
* **Standards alignment** — flag evaluation should follow a published interface so we are not inventing a fourth flag-evaluation API on top of the three already in the wild.
* **Compliance with ADR 0001** — the SDK lives in `Infrastructure/`; call sites depend on a domain-level interface, not on the SDK.

## Considered Options

### Option A — Hand-rolled boolean column on `system_setting`

Reuse the M4 `system_setting` table. Each flag is a row; reads happen on every request through the existing repository.

* (+) Smallest possible footprint. Zero new dependencies.
* (−) No standard evaluation API. Each call site queries a setting by key — easy to misuse, hard to mock in tests.
* (−) No structured rollout (percentages, target groups) without bolting it on.
* (−) Audit log and circuit breaker would be hand-rolled too. Three new bespoke systems on a tiny base.

### Option B — Third-party SaaS (LaunchDarkly, GrowthBook Cloud, …)

* (+) Mature dashboards, percentage rollouts, targeting rules out of the box.
* (−) Each operator deployment now has a runtime dependency on a third-party SaaS. SASO is self-hosted by intent.
* (−) Many target deployments are air-gapped or behind corporate proxies that block outbound traffic.
* (−) Cost.

### Option C — OpenFeature PHP SDK + a SASO-owned DB provider

OpenFeature is an open standard for flag evaluation; the PHP SDK exposes a stable `Client` API (`getBooleanValue`, `getStringValue`, `getNumberValue`, `getObjectValue`). We implement a `Saso\Infrastructure\FeatureFlag\DbProvider` that reads from a `feature_flag` table; the SDK handles the surrounding ergonomics (variant resolution, error handling, hooks, evaluation context).

Self-healing is a separate concern: an `error_log_aggregate` table records errors per `feature_key`, a cron script (or, on cron-less hosts, a synchronous "tail-of-request" trigger) sweeps the aggregate and toggles `feature_flag.enabled = 0` when a threshold is crossed, writing the reason to `feature_flag_audit`.

* (+) Standard API. Call sites depend on the OpenFeature client interface, not on our table schema.
* (+) The SDK gives us hooks (logging, metrics) without bespoke wiring.
* (+) Provider implementation stays small (a few hundred lines) — most complexity already lives in the SDK.
* (+) We control the storage, so we can add domain-specific columns (`error_threshold`, `auto_disabled_at`, `auto_disable_reason`) without forking anything.
* (−) New dependency. The OpenFeature PHP SDK is at 1.x but ecosystem maturity lags Java / Node.

### Option D — OpenFeature SDK + an existing community provider (e.g. flagd, Unleash, GrowthBook OSS)

* (+) Reuses an external evaluation engine; gets percentage rollouts + audit "for free".
* (−) Adds a second deployable component (flagd / Unleash / GrowthBook server). Shared-hosting operators run a single PHP app; a second daemon is a non-starter.
* (−) Self-healing on application errors still has to be glued in — these engines do not know about our error catalogue.

## Decision Outcome

**Chosen option: C — OpenFeature PHP SDK + a SASO-owned DB provider with a cron circuit breaker.**

### Tables

```sql
-- The flag itself.
CREATE TABLE feature_flag (
    id                BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    key_name          VARCHAR(120) NOT NULL UNIQUE,
    description       VARCHAR(500) NOT NULL,
    enabled           TINYINT(1) NOT NULL DEFAULT 0,
    rollout_percent   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    conditions        JSON NULL,                          -- targeting rules
    error_threshold   INT UNSIGNED NOT NULL DEFAULT 0,    -- 0 = never auto-disable
    error_window_min  INT UNSIGNED NOT NULL DEFAULT 60,
    auto_disabled_at  DATETIME NULL,
    auto_disable_reason VARCHAR(500) NULL,
    created_at        DATETIME NOT NULL,
    updated_at        DATETIME NOT NULL
);

-- Per-flag error counts in time buckets, written from the global
-- exception handler (cf. ADR 0004) when ProblemExceptionHandler can
-- attribute the failure to a feature.
CREATE TABLE error_log_aggregate (
    id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    feature_key   VARCHAR(120) NOT NULL,
    error_code    VARCHAR(40) NOT NULL,
    count         INT UNSIGNED NOT NULL,
    window_start  DATETIME NOT NULL,
    window_end    DATETIME NOT NULL,
    KEY idx_feature_window (feature_key, window_start)
);

-- Append-only audit of every flip, including circuit-breaker events.
CREATE TABLE feature_flag_audit (
    id           BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    flag_key     VARCHAR(120) NOT NULL,
    old_enabled  TINYINT(1) NOT NULL,
    new_enabled  TINYINT(1) NOT NULL,
    changed_by   VARCHAR(120) NOT NULL,                   -- 'circuit_breaker' | member id
    changed_at   DATETIME NOT NULL,
    reason       VARCHAR(500) NULL,
    KEY idx_flag (flag_key, changed_at)
);
```

### Code shape

```
src/
├── Domain/
│   └── Feature/
│       ├── FeatureKey.php                  # value object
│       ├── FeatureFlag.php                 # aggregate
│       ├── EvaluationContext.php           # member + tenant + locale
│       └── Repository/
│           └── FeatureFlagRepository.php   # interface
└── Infrastructure/
    └── FeatureFlag/
        ├── DbProvider.php                  # OpenFeature\Provider implementation
        ├── PdoFeatureFlagRepository.php
        ├── ErrorAggregator.php             # writes error_log_aggregate
        └── CircuitBreaker.php              # invoked by cron + fallback
```

### Circuit breaker — cron + fallback

* `scripts/feature_flag_circuit_breaker.php` runs every 5 minutes from cron. It scans `error_log_aggregate` against each flag's `error_threshold` over the configured `error_window_min`, flips offending flags off, and writes the audit row.
* For cron-less hosts (some shared-hosting deployments), a tail-of-request middleware checks "have we run the breaker in the last 60 minutes?" and if not, triggers it synchronously after the response is flushed. This is best-effort: a low-traffic instance may go longer without a check, but the breaker still runs eventually.
* Operators can disable the synchronous fallback via `FEATURE_FLAG_INLINE_BREAKER=0` in `.env` once they confirm cron is wired.

### Evaluation pipeline

```
Application code
    └─> OpenFeature\Client::getBooleanValue('checkout.new_flow', false, ctx)
        └─> Saso\Infrastructure\FeatureFlag\DbProvider::resolveBooleanValue(...)
            ├─> reads from PdoFeatureFlagRepository (request-scoped cache)
            └─> applies rollout_percent + conditions against ctx
```

Call sites depend on the OpenFeature `Client`, not on our provider. Tests inject the SDK's `InMemoryProvider`.

### Observability

* Every evaluation that returns the default value (provider couldn't resolve the flag) emits a Monolog warning with the flag key. Misspelled or unregistered flags surface in the log instead of silently using the default.
* Circuit-breaker activations emit a structured Monolog `error` and (in M4) a Slack/email notification.

## Consequences

* Application code learns one API (OpenFeature `Client`) and never touches the database directly.
* Operators get a single admin screen (M4) to flip flags; the audit row makes accidental flips reviewable.
* Bad rollouts auto-disable, and the audit log records the breaker event so post-mortems do not start with "wait, who turned this off?".
* Self-healing works on cron-equipped and cron-less hosts alike. We accept that cron-less hosts have weaker freshness guarantees.
* Adopting the OpenFeature standard means future migration to a different provider (flagd, GrowthBook OSS) is a wiring change, not a domain change.
* The `feature_flag` + `error_log_aggregate` + `feature_flag_audit` tables add ~40 KB per 10 K aggregate rows. We accept this as a cost of operability.
