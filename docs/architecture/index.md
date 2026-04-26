# Architecture

SASO is being progressively refactored from its v2.4 layout into a Clean Architecture / DDD shape. **This page is a stub** — the canonical layout doc lands as the M3-M4 PRs migrate code into `src/`.

## Today (post-M2)

```
/
├── auth/, item/, category/, …    legacy modules — DI containers + usecases + presenters + views
├── entity/                       legacy domain entities  (Member uses Argon2id since M1)
├── repository/                   legacy persistence (PDO + DbPrepare interface)
├── framework/                    legacy DI container, Router, UserCompiler
├── util/                         monads + helpers; the M1 rewrites (EnvLoader, UploadValidator, CSRFtoken)
├── extention/TCPDF/              bundled PDF generator (LGPL)
├── src/                          NEW — empty placeholder for Saso\ namespace (M3+)
├── tests/Unit/                   PHPUnit cases for util + entity (52 cases as of M2-B)
├── docker/                       development image + Apache vhost + php.ini overrides
├── docs/                         this site
├── migrations/                   hand-applied SQL files; Phinx replaces this in M4
├── ConfigLoader.php              loads config.json + .env overlay
├── ClassLoader.php               legacy autoloader for `saso\`
└── index.php                     front controller — runs vendor/autoload.php first, then ClassLoader
```

## Target (post-M4)

```
/
├── public/                       DocumentRoot
│   └── index.php                 thin front controller
├── src/
│   ├── Domain/                   entities + value objects + domain services
│   ├── Application/              use cases + DTOs + presenter interfaces
│   ├── Infrastructure/
│   │   ├── Persistence/Pdo/      repositories (legacy `repository/` migrated here)
│   │   ├── Auth/Oidc/            jumbojett wrapper for OIDC
│   │   ├── Auth/Saml/            onelogin/php-saml wrapper for SAML
│   │   ├── FeatureFlag/          OpenFeature provider
│   │   ├── Translation/          symfony/translation loader
│   │   └── Pdf/                  TCPDF adapter
│   └── Presentation/
│       ├── Web/                  PHP templates (legacy view layer)
│       ├── Api/V1/               REST controllers + OpenAPI annotations
│       └── Http/                 router, middleware, ProblemDetails
├── legacy/                       pre-fork code being incrementally retired
├── tests/{Unit,Integration,Feature,e2e}/
└── docs/                         (this tree)
```

The Strangler Fig migration runs across **M3 (REST + i18n + Errors)** and **M4 (DDD + Feature Flag + Web Settings)**. ADR 0001 (planned) records the decision; subsequent ADRs document each significant move.

## See also

- [Workflow](../development/workflow.md) — how PRs are sliced.
- [ADR Index](adr.md) — design decision log.
