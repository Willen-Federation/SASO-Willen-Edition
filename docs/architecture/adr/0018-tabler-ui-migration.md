# ADR 0018 — Adopt Tabler as the single design system

- **Status:** Accepted
- **Date:** 2026-05-04
- **Supersedes:** [ADR 0017 — TailAdmin UI migration](./0017-tailadmin-ui-migration.md)

## Context

Until M5 the codebase ran three style systems in parallel:

- **Bootstrap 5.0.2** loaded from a CDN by `root/template/root.php` (login, the new `/auth/provider` wizard, several legacy screens).
- **Tailwind + TailAdmin tokens** under `tailadmin/`, `css/tailadmin.css`, `css/app.css`, `tailwind.config.js`, plus the `ui()` partials under `root/template/_components/`.
- **Ad-hoc inline styles** scattered across templates.

Symptoms:
- `/auth/provider` (Bootstrap cards) and `/auth/providers/` (TailAdmin `ui('card')` + `ui('table')`) looked like different applications.
- Adding a screen forced a coin-flip between Bootstrap classes and `ui()` partials.
- Two CSS bundles shipped on most pages.
- The TailAdmin `tailwind.config.js` required a Node toolchain (`tailwindcss -i css/input.css -o css/app.css`) just to make a class-name change visible — but `css/app.css` and `css/tailadmin.css` were not actually referenced from `root/template/root.php` at all, so the build artefacts were dead weight.

## Decision

Adopt **[Tabler](https://github.com/tabler/tabler) 1.x** as the single design system for both *internal* and *external* HTML surfaces. Remove Bootstrap, TailAdmin tokens, and the entire `tailadmin/` directory once parity is reached.

Why Tabler:
- Bootstrap 5.3 compatible markup → existing Bootstrap templates migrate as a class-rename.
- Built-in admin shell covers the layouts in `root/template/_layout/`.
- Ships its own icon font (`@tabler/icons-webfont`) — drops Bootstrap Icons + the `iconHeroicon` partial's hand-rolled SVGs.
- MIT, actively maintained, no Node toolchain required because we load both the CSS and the JS from `cdn.jsdelivr.net`.

## Consequences

- `root.php` loads exactly two stylesheets: Tabler core + Tabler Icons (via CDN), plus a tiny project-local `css/style.css` (legacy `.hidden`/`.blue`/`.red`/`.green` classes used by `categoryPath` rendering).
- All `ui()` partials (`button`, `card`, `table`, `alert`, `pagination`, `tabs`, `formField`, `modal`) emit Tabler markup (PR #65 + this PR).
- Templates outside the partial wrappers — auth, item, label, shelf, image, barcode, admin, installer, start, category, verify, member, search — were rewritten to use Tabler `.card` shells and `form-control`/`form-select`/`form-check` inputs.
- TailAdmin tokens (`text-brand-*`, `bg-brand-*`, `text-error-500`, `ta-alert-*`, `ta-badge-*`) were swept out via bulk sed and replaced with Bootstrap utilities (`text-primary`, `bg-primary`, `text-danger`, `alert alert-*`, `badge bg-*`).
- `tailadmin/`, `css/tailadmin.css`, `css/app.css`, `css/input.css`, and the root `tailwind.config.js` are deleted.
- `package.json` no longer needs `tailwindcss`, `@tailwindcss/forms`, or `@tailwindcss/typography` — those are dropped along with the `build`/`watch`/`dev` scripts that drove them.
- ADR 0017 is superseded but the file is kept for historical reference.

## Alternatives considered

- **Stay with TailAdmin.** Rejected — the migration was already half-done, the CSS bundle wasn't actually loaded in production, and the template style mix was confusing for both new contributors and end-users.
- **Compile Tabler through Tailwind.** Rejected for now — the CDN pin is sufficient for an installable PHP app. Self-hosting Tabler can be revisited if/when CDN dependency becomes a deployment problem.
- **Headless / Stimulus-based component rebuild.** Out of scope — the goal here is visual cohesion, not a JS-framework migration.

## References

- GitHub issue [#60 — Adopt Tabler design system across all internal and external screens](https://github.com/willenjp/SASO-Willen-Edition/issues/60).
- GitHub issue [#61 — Migrate every form to Tabler form controls](https://github.com/willenjp/SASO-Willen-Edition/issues/61).
- PR [#65](https://github.com/willenjp/SASO-Willen-Edition/pull/65) — Phase 0 + Phase 1 (foundation + component partials).
- PR [#66](https://github.com/willenjp/SASO-Willen-Edition/pull/66) — Phase 2 + Phase 3 (per-template migration + cleanup).
