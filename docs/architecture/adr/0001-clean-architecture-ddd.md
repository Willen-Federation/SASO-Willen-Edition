# 0001 — Adopt Clean Architecture + DDD layout under `src/`

* Status: accepted
* Date: 2026-04-26
* Deciders: Willen Federation contributors

## Context and Problem Statement

The fork inherits a v2.4-era layout where DI containers, use cases, repositories, and presenters live as flat sibling directories at the repository root (`auth/`, `item/`, `category/`, …). The structure already separates intent from infrastructure, but it does so by convention rather than by namespace, and there is no formal boundary between the domain model and the framework code that drives it (`framework/`, `repository/`).

We need to evolve the codebase toward a structure that:

1. lets contributors locate the domain rules of a feature without grepping framework code;
2. makes the cost of swapping infrastructure (PDO → Doctrine, file storage → S3, TCPDF → wkhtmltopdf, …) bounded and visible;
3. supports the M3-M5 work — REST API, OIDC/SAML, Feature Flags, Web settings — without bolting them onto the legacy controllers; and
4. does not require a stop-the-world rewrite, because the application has working production users.

## Decision Drivers

* **Backward compatibility** — existing endpoints must keep working through every refactor PR. Operators should be able to deploy `main` at any commit.
* **Reviewability** — each PR must stay small enough for a single reviewer. A big-bang move of `entity/`, `repository/`, `framework/` would be unreviewable.
* **Test surface** — domain logic should be unit-testable without spinning up MariaDB or Apache.
* **Composer + PSR-4 alignment** — M2 already introduced Composer with `Saso\\` mapped to `src/`. The new layout has to live there to benefit from the autoloader.
* **Clarity over orthodoxy** — we will not import every Clean Architecture / DDD ceremony. Aggregates, repositories, and use cases are the load-bearing pieces; tactical patterns (event sourcing, CQRS, sagas) stay out of scope.

## Considered Options

### Option A — Big-bang rewrite

Move every legacy module into `src/Domain|Application|Infrastructure|Presentation/` in a single PR.

* (+) End state is reached immediately; no ambiguity about where new code belongs.
* (−) Unreviewable diff. Every contributor blocked until merged. High regression risk against zero passing integration tests.
* (−) Conflicts with the M3 milestone scope (REST + i18n + Errors), which would all be blocked behind it.

### Option B — Strangler Fig: keep legacy at root, build new code under `src/`

Leave `auth/`, `entity/`, `repository/`, `framework/`, `util/`, etc. exactly where they are. Add new functionality (`/api/v1/*`, OpenFeature, OIDC, Problem Details renderer, translator) under `src/Domain|Application|Infrastructure|Presentation/`. The legacy `ClassLoader` keeps loading `saso\…`; Composer's PSR-4 autoloader loads `Saso\…`. Old code is moved into the new tree only when it is touched anyway, in PRs scoped to a single bounded context.

* (+) Reviewable PRs throughout. Each milestone ships independently.
* (+) Production stays deployable on every commit.
* (+) The new code is born in the target shape; reviewers can enforce the boundary without arguing about exceptions.
* (−) Two layouts coexist for the duration of M3-M4. Contributors need to know which side a piece of code lives on.
* (−) Some duplication during transition (e.g. password verification temporarily exists on both sides).

### Option C — Hexagonal / Ports & Adapters first, DDD vocabulary deferred

Same physical move as Option B but without the `Domain/Application/…` naming — use `Core/`, `Adapters/Driving/`, `Adapters/Driven/` instead.

* (+) Slightly less ceremony for contributors who are not familiar with DDD.
* (−) The plan and follow-on ADRs already use Domain / Application / Infrastructure / Presentation. Two vocabularies for the same idea is worse than the marginal learning cost.

## Decision Outcome

**Chosen option: B — Strangler Fig with the four-layer DDD layout under `src/`.**

The target structure is:

```
src/
├── Domain/          entities, value objects, domain services, aggregate roots
├── Application/     use cases (one class per behaviour) + DTOs + presenter interfaces
├── Infrastructure/  Persistence/Pdo, Auth/Oidc, Auth/Saml, FeatureFlag, Translation, Pdf, …
└── Presentation/    Web (templates), Api/V1 (controllers), Http (router, middleware, ProblemDetails)
```

Bounded contexts under `Domain/` follow the existing module boundaries: `Item`, `Category`, `Member`, `Label`, `Shelf`, `Auth`, `Feature`, plus a `Shared/` kernel for IDs, error codes, and value objects used across contexts.

Migration rules:

1. **No code is moved without a test.** When we lift a class out of `entity/` or `repository/`, it lands in `src/` with at least one unit test attached.
2. **One context per PR.** A PR that moves `entity/Item.php` does not also move `entity/Category.php`. PRs are reviewed for behavioural parity, not just for the move.
3. **Legacy keeps its namespace.** `saso\…` stays valid via `ClassLoader.php`. New code is `Saso\…`. Both autoloaders coexist (Composer first via `vendor/autoload.php`, ClassLoader fallback in `index.php`).
4. **Strangler exits one entry at a time.** When the last call site of a legacy class is removed, the legacy class is deleted in the same PR.

## Consequences

* M3 (REST + i18n + Errors) and the OIDC + SAML work in M3-E build directly under `src/`. They never touch legacy directories.
* Contributors get an unambiguous answer to "where does new code go?": **`src/`** — and the layer is determined by what the code does (domain rule vs. infrastructure adapter vs. HTTP entry point).
* The repository carries two layouts for the duration of M3 + M4. We accept this as the cost of incremental delivery.
* PHPStan level 6 (and later 7-8) is scoped to `src/` and the M1 utility files in `util/`. The legacy tree is checked at a lower level until migrated, which is what `phpstan.neon.dist` already encodes.
* Subsequent ADRs (0002 OpenAPI, 0003 Pluggable IdP, 0004 Problem Details) all assume this layout. They reference paths under `src/` directly.
