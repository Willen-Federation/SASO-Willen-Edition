# 0002 — OpenAPI 3.1 as the single source of truth for `/api/v1/*`

* Status: accepted
* Date: 2026-04-26
* Deciders: Willen Federation contributors

## Context and Problem Statement

The legacy stack expresses HTTP routing through a hand-curated `request.json` consumed by `framework/Router.php`, with no machine-readable contract for the request/response shape. M3 introduces a parallel REST surface at `/api/v1/*` that must serve multiple kinds of clients — the existing PHP UI as it migrates, future SPAs, mobile apps, and external integrations.

We need to decide where the contract for those endpoints lives, and which artefact wins when generated code, documentation, and runtime behaviour disagree.

## Decision Drivers

* **One contract, many clients** — the API surface will be consumed by code we do not control. A documentation-only spec that drifts from the running server is worse than no spec at all.
* **Schema-first review** — endpoint shape changes should be reviewable as a YAML diff before any controller code is touched. This is also what enables design review with non-PHP contributors.
* **Tooling leverage** — Swagger UI, request/response validators, and SDK generators all expect OpenAPI. We do not want to reinvent any of these.
* **Existing investment** — `request.json` already maps URLs to handlers. Whatever we adopt has to coexist with it long enough to migrate the legacy screens.
* **Runtime cost** — the chosen approach must not add a build step that breaks the "git clone and serve" promise on shared hosting.

## Considered Options

### Option A — Annotation-driven (`zircote/swagger-php` reading PHPDoc / attributes on controllers)

Controllers are annotated with `#[OA\Get(...)]` or PHPDoc tags; a build step extracts the spec.

* (+) Spec stays close to the code that implements it.
* (+) IDE jumps from spec to handler are trivial.
* (−) The spec is a side effect of the implementation. You cannot review it before writing the controller, and refactors that move handlers cause cosmetic spec churn.
* (−) Schema-driven review with non-PHP contributors becomes "read these annotations across N files and assemble the contract in your head".
* (−) Build step required to materialise the YAML — something that breaks if Composer is unavailable on the host.

### Option B — Code-first runtime (PHP-Attributes resolved at request time, no static spec)

Controllers carry routing + schema attributes; the framework reflects on them at request time and validates inputs/outputs against them. No `openapi.yaml` is committed.

* (+) Zero drift — there is no separate document to diverge.
* (−) No artefact for SDK generators or Swagger UI without an extra extraction step.
* (−) Runtime reflection cost on every request unless cached, and the cache becomes a deploy concern.
* (−) External consumers cannot view the contract without running the server.

### Option C — Schema-first (`config/openapi.yaml` is the source; controllers conform to it)

A single `config/openapi.yaml` (OpenAPI 3.1) is committed and reviewed as the contract. `nikic/fast-route` is configured from a paths/methods map derived from it. Controllers receive validated DTOs; responses are validated in dev/CI against the spec. Swagger UI is rendered from the same YAML.

* (+) Reviewable as a diff before any handler is written. The spec PR can land before the implementation PR.
* (+) Single artefact for Swagger UI, SDK generators, and contract tests. No build step needed for consumers — the YAML is checked in.
* (+) Drift detection is automated: CI fails if a response shape does not match the spec.
* (−) Controllers must be kept consistent with the YAML by hand or by a validator. Without enforcement, the YAML rots like any committed doc.
* (−) Slightly more upfront ceremony than annotations for trivial endpoints.

## Decision Outcome

**Chosen option: C — Schema-first with `config/openapi.yaml` as the source of truth.**

Implementation rules:

1. **YAML is the contract.** `config/openapi.yaml` is the single committed artefact. Controllers in `src/Presentation/Api/V1/` MUST conform to it. PRs that change behaviour update the YAML in the same commit.
2. **Routing reads the YAML.** `nikic/fast-route` is configured from the spec at boot. Adding an endpoint = editing the YAML and adding a controller; there is no second routing file to keep in sync.
3. **CI validates conformance.** The test suite issues real requests against the controllers and validates request + response payloads against the spec. Drift breaks CI, not production.
4. **Swagger UI is rendered from the same YAML** via `mkdocs-render-swagger-plugin` on the docs site, and via a static `/api/v1/docs` route in the application itself.
5. **`request.json` is not extended.** New endpoints land under `/api/v1/*` and exist only in the YAML. Existing PHP screens keep using `request.json` until their feature is migrated; at that point both the YAML route and the legacy entry are added in one PR and the legacy entry is removed in a follow-up.
6. **OpenAPI version: 3.1.** It aligns with JSON Schema 2020-12, which lets us reuse the same schemas for runtime validation (`opis/json-schema`).

## Consequences

* The spec ships with the code; consumers can build clients without running the application.
* Reviewers can approve API contracts before any controller exists. This is especially useful for OIDC/SAML callbacks (ADR 0003) where the wire shape is non-trivial.
* Any new endpoint requires editing two places (YAML + controller) plus a contract test. Trivial endpoints carry slightly more ceremony than they would with annotations; we accept this in exchange for a reviewable contract.
* Error responses follow the Problem Details schema defined in ADR 0004 — `application/problem+json`, with the `code` extension for `SASO-…` identifiers.
* SDK generation (TypeScript, Go, …) becomes a downstream concern that does not require any change to this repository.
