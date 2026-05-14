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
| [0009](adr/0009-multi-llm-gateway.md) | Multi-LLM gateway with provider abstraction (OpenAI / Gemini / Claude) | 2026-04-26 | M6 |
| [0010](adr/0010-vector-search-via-opensearch.md) | Vector embeddings + image search via OpenSearch k-NN | 2026-04-26 | M6 |
| [0011](adr/0011-flexible-attributes-and-locations.md) | Flexible item attributes (EAV) + storage location codes | 2026-04-26 | M6 |
| [0012](adr/0012-search-and-cache-infrastructure.md) | Search + cache infrastructure: OpenSearch primary, Redis cache | 2026-04-26 | M6 |
| [0013](adr/0013-symfony-messenger-queue.md) | Background job queue via Symfony Messenger | 2026-04-26 | M6 |
| [0014](adr/0014-flutter-pairing-and-mcp-server.md) | Flutter device pairing (RFC 8628) + MCP server endpoint | 2026-04-26 | M6 |
| [0015](adr/0015-plugin-system.md) | Plugin system: Composer-installed packages with extension points | 2026-04-26 | M6 |
| [0016](adr/0016-english-as-default-locale.md) | English-as-default + extract legacy JA strings into i18n catalogue | 2026-04-26 | M6 |
| [0017](adr/0017-tailadmin-ui-migration.md) | TailAdmin Free Tailwind UI migration (Bootstrap 5 → Tailwind v3) | 2026-04-28 | M5 |
| [0018](adr/0018-tabler-ui-migration.md) | Adopt Tabler as the single design system (supersedes ADR 0017) | 2026-05-04 | M5 |

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
