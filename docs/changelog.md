# Changelog

The full changelog lives at the repository root in [`CHANGELOG.md`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/CHANGELOG.md). It follows the [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) format with an `[Unreleased]` section that aggregates everything between releases.

When the first tagged release of this fork ships in **M5**, the in-site changelog will be auto-generated from the `CHANGELOG.md` source so this page stops being a redirect.

## Recent activity

- **M5 (UI Modernization)** — TailAdmin integration ([ADR 0017](architecture/adr/0017-tailadmin-ui-migration.md)); Bootstrap 5 CDN removed from all templates; full dark-mode coverage across item, shelf, barcode, admin, and category screens; Tabler adoption accepted ([ADR 0018](architecture/adr/0018-tabler-ui-migration.md)); CSV bulk import/export for items; custom status columns; barcode auto-register on the dashboard; sidebar permission filtering; CI upgraded to actions/checkout and actions/cache v5.
- **M4 (Auth Providers + Feature Flags + Mobile)** — Pluggable IdP with OIDC, SAML, Auth0, Cognito, and Firebase adapters; `auth_provider` DB table; admin UI for creating/editing/testing providers; JIT member provisioning; OpenFeature SDK with DB-backed provider; feature-flag admin UI; QR-based Flutter device pairing; `mobile/tokens` management; `APP_KEY` AES-256-GCM encryption for stored secrets; `system_setting` admin UI; Phinx migrations for all M4 schema changes.
- **M3 (REST API + i18n + Errors)** — REST surface at `/api/v1/*` backed by OpenAPI 3.1 spec ([ADR 0002](architecture/adr/0002-openapi-as-source-of-truth.md)); RFC 7807 Problem Details with `SASO-DOMAIN-NNNN` codes ([ADR 0004](architecture/adr/0004-rfc7807-problem-details.md)); Clean Architecture `src/` layout ([ADR 0001](architecture/adr/0001-clean-architecture-ddd.md)); AuthProvider contract ([ADR 0003](architecture/adr/0003-pluggable-idp.md)); bilingual docs site (English + Japanese).
- **M2 (Tooling & Composer)** — Composer foundation, PHPUnit / PHPStan / PHP-CS-Fixer baseline (52 tests), Docker dev stack, MkDocs site (this site).
- **M1 (Security Hotfix)** — Argon2id passwords, random session-bound CSRF tokens, hardened session cookies, in-app HTTPS enforcement, `.env` overlay, content-driven upload validation.
- **M0 (Stabilize)** — repository hygiene, OSS scaffolding, GitHub Flow protections, English-first bilingual docs.

See the project [milestones page](https://github.com/Willen-Federation/SASO-Willen-Edition/milestones) for what is in flight.
