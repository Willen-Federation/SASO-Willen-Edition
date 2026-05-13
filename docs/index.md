# SASO — Willen Edition

**Open-source inventory and warehouse management in PHP.** Items, categories (nested set model), barcodes, label printing via TCPDF, and shelf management — runnable on standard shared hosting and on Docker.

This documentation site covers operating the application, contributing to it, and the architectural decisions guiding the modernization roadmap (M0–M5).

> [日本語](/ja/) / Original Japanese README at [`ORIGINAL_README.md`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/ORIGINAL_README.md)

## What you can read here

| Section | For whom |
|---|---|
| [Getting Started](getting-started/index.md) | Operators standing up an instance for the first time |
| [Configuration](getting-started/configuration.md) | Operators tuning `.env` / `config.json` / system settings |
| [Architecture](architecture/index.md) | Contributors understanding the Clean Architecture / DDD layout |
| [Development](development/index.md) | Contributors writing code, tests, or docs |
| [Security](security.md) | Anyone — disclosure policy and operator hardening |
| [API Reference](api.md) | API clients consuming `/api/v1/*` |
| [Error Codes](error-codes.md) | Anyone debugging a `SASO-*` error code |

## Status

The fork is in active modernization. **M0 (Stabilize)**, **M1 (Security Hotfix)**, **M2 (Tooling & Composer)**, **M3 (REST API + i18n + Errors)**, and **M4 (Auth Providers + Feature Flags + Mobile Pairing)** are complete. **M5 (UI Modernization — TailAdmin → Tabler)** is underway; see [ADR 0017](architecture/adr/0017-tailadmin-ui-migration.md) and [ADR 0018](architecture/adr/0018-tabler-ui-migration.md) for the rationale. See the [Changelog](changelog.md) for the full activity log.

## License

Code authored by Japan Standards Organization (日本標準機構) and Willen Federation contributors is licensed under the [GNU GPL v3](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/LICENSE). Bundled third-party libraries under `extention/` retain their original licenses (e.g. TCPDF: LGPLv3).
