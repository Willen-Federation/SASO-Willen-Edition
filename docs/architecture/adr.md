# ADR Index

Architecture Decision Records (ADRs) capture significant choices and the reasoning behind them. We use the [MADR](https://adr.github.io/madr/) format.

ADR files will live under `docs/architecture/adr/0001-*.md` once the first decision lands in **M3**. Until then this page reserves the slot.

## Planned ADRs

| Number | Topic | Milestone |
|---|---|---|
| 0001 | Adopt Clean Architecture + DDD layout under `src/` | M3 |
| 0002 | OpenAPI 3.1 as the single source of truth for `/api/v1/*` | M3 |
| 0003 | Pluggable IdP (`AuthProvider` interface) with OIDC + SAML implementations | M3 |
| 0004 | RFC 7807 Problem Details + `SASO-DOMAIN-NNNN` codes | M3 |
| 0005 | OpenFeature SDK + DB-backed provider for Feature Flags | M4 |
| 0006 | `system_setting` DB table editable from the admin Web UI | M4 |
| 0007 | Phinx for schema migrations | M4 |
| 0008 | Vendor-bundled release ZIP for shared-hosting deploys | M5 |

## Format

Each ADR follows MADR's slim template:

```
# 0001 — Adopt Clean Architecture + DDD layout under src/

* Status: accepted | proposed | superseded by ADR-NNNN
* Date: YYYY-MM-DD
* Deciders: @owner-handle, ...

## Context and Problem Statement
## Decision Drivers
## Considered Options
## Decision Outcome
## Consequences
```

ADRs are immutable once accepted. A later decision that replaces or modifies an earlier one creates a new ADR that supersedes the old one.
