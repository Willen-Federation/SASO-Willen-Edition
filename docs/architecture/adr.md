# ADR Index

Architecture Decision Records (ADRs) capture significant choices and the reasoning behind them. We use the [MADR](https://adr.github.io/madr/) format.

ADRs live under `docs/architecture/adr/` and are numbered sequentially. They are immutable once accepted — a later decision that replaces or modifies an earlier one creates a new ADR that supersedes the old one.

## Accepted

| Number | Topic | Date | Milestone |
|---|---|---|---|
| [0001](adr/0001-clean-architecture-ddd.md) | Adopt Clean Architecture + DDD layout under `src/` | 2026-04-26 | M3 |
| [0002](adr/0002-openapi-as-source-of-truth.md) | OpenAPI 3.1 as the single source of truth for `/api/v1/*` | 2026-04-26 | M3 |
| [0003](adr/0003-pluggable-idp.md) | Pluggable IdP (`AuthProvider` interface) with OIDC + SAML implementations | 2026-04-26 | M3 |
| [0004](adr/0004-rfc7807-problem-details.md) | RFC 7807 Problem Details + `SASO-DOMAIN-NNNN` codes | 2026-04-26 | M3 |
| [0005](adr/0005-openfeature-with-db-provider.md) | OpenFeature SDK + DB-backed provider with cron circuit breaker | 2026-04-26 | M4 |
| [0006](adr/0006-system-setting-web-ui.md) | `system_setting` DB table editable from the admin Web UI | 2026-04-26 | M4 |
| [0007](adr/0007-phinx-migrations.md) | Phinx for schema migrations | 2026-04-26 | M4 |

## Planned

| Number | Topic | Milestone |
|---|---|---|
| 0008 | Vendor-bundled release ZIP for shared-hosting deploys | M5 |

## Format

Each ADR follows MADR's slim template:

```
# NNNN — short title

* Status: accepted | proposed | superseded by ADR-NNNN
* Date: YYYY-MM-DD
* Deciders: @owner-handle, ...

## Context and Problem Statement
## Decision Drivers
## Considered Options
## Decision Outcome
## Consequences
```

Numbers are assigned at PR-open time. If two ADRs collide, the later one is renumbered before merge.
