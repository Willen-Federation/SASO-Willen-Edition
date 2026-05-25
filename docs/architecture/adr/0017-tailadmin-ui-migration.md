# 0017 — TailAdmin Free Tailwind UI migration (Bootstrap 5 → Tailwind v3)

* Status: accepted
* Date: 2026-04-28
* Deciders: Willen Federation contributors
* Supersedes: parts of legacy Bootstrap usage (no prior ADR)

## Context

The legacy admin UI is built with Bootstrap 5.0.2 loaded from CDN, plain PHP templates, and zero build pipeline. Operators report that screens are hard to scan, the navigation is shallow (one-level top navbar), there is no dark mode, no accessible focus styling, no language switcher, and Japanese strings are hard-coded inside templates. New M6 features (label-first workflow, shelf map, data verification, auth-provider admin, feature-flag UI) require a sidebar layout the existing navbar cannot host.

[TailAdmin Free Tailwind](https://github.com/TailAdmin/tailadmin-free-tailwind-dashboard-template) (MIT) provides a sidebar+header dashboard shell that matches the new feature layout, supports dark mode, and uses Tailwind utility classes. It ships HTML-with-Alpine — no React framework — which is compatible with our plain-PHP template approach.

The project explicitly opted out of Node-in-production (no `package.json` today; deployment surface is shared hosting + Composer-only).

## Decision

1. Adopt **TailAdmin Free Tailwind v3** as the new UI base.
2. Build CSS via the **tailwindcss standalone CLI binary** (single static Go executable, no Node ecosystem). Builds are committed: `css/tailadmin.css` ships as a build artefact under git so production servers stay Node-free.
3. Vendor **Alpine.js 3.14.1** + plugins (`@alpinejs/persist`, `@alpinejs/focus`) as committed `js/alpine*.min.js`. `make tailadmin-vendor` re-pulls pinned versions.
4. Replace `root/template/root.php` Bootstrap shell with TailAdmin sidebar+header layout. Per-page Bootstrap markup is migrated phase-by-phase.
5. Introduce reusable PHP partials at `root/template/_layout/` (sidebar, header, breadcrumb, footer, skip-link, lang-switcher, installer-alert) and component helpers at `root/template/_components/` (card, formField, button, table, modal, alert, pagination, tabs, iconHeroicon). A single `ui($name, $args)` global helper (registered via Composer `autoload.files`) gives templates a one-line render API.
6. Add a **language switcher** to the header — POSTs to `/locale/set/{lc}`, writes `saso_locale` cookie (365d, SameSite=Lax). `LocaleResolver` extended with a 4th source: cookie (precedence: query > member preference > cookie > Accept-Language > default).
7. **Accessibility baseline**: every page renders a single `<header role="banner">`, `<nav role="navigation">`, `<main id="main-content" tabindex="-1">`, `<footer role="contentinfo">`. Skip-link is the first focusable element. `formField` helper enforces `<label for>` / `aria-describedby`. Focus-visible rings apply globally. Keyboard nav: modals trap focus via `@alpinejs/focus`, ESC closes, Tab order follows DOM.
8. **Dark mode** via `class="dark"` on `<html>`, persisted in `localStorage('saso.theme')`. No JavaScript flicker thanks to inline pre-paint application in `js/tailadmin.js`.
9. RTL is **out of scope** for v1. Logical properties (`ms-*`, `me-*`) are optional; the catalogue uses `dir="ltr"`.

## Why standalone CLI rather than Node toolchain

- Production deploys run shared-hosting Apache + mod_php + Composer. Adding `package.json` would make every deployment depend on Node, contradicting the project's explicit positioning ("Open-source inventory & warehouse management in PHP — self-hosted").
- The standalone binary handles 95% of upstream TailAdmin's needs (utility scanning, JIT, plugins via `@layer`). The 5% gap is upstream's v4-specific syntax (`@theme`, `@custom-variant`); we re-express those concepts in v3 `theme.extend` + custom `@layer` blocks (see `tailadmin/tailwind.config.js` + `tailadmin/input.css`).
- Builds run in milliseconds; CI gating is trivial (size delta check on `css/tailadmin.css`).

## Consequences

**Positive**

- Modern, dense, scannable UI; sidebar accommodates new feature groupings (label-first, shelf map, verification, auth providers, feature flags).
- Dark mode + accessibility baseline + language switcher land in a single migration.
- Future contributors can edit `*.php` templates without learning a new templating engine.
- TailAdmin's MIT licence permits unrestricted use and adaptation.

**Negative**

- The committed `css/tailadmin.css` artefact will appear in diffs whenever the CSS rebuilds. We mitigate by verifying size deltas in `make qa`.
- Mixed-styling period — Bootstrap classes survive in templates that have not yet migrated. A `grep` lint in the QA pipeline fails the build once the migration is complete (see Phase 7 in the plan file).
- The standalone tailwindcss CLI is platform-specific (linux-x64 by default). The `Makefile` defaults to that; cross-platform contributors override `TAILWINDCSS_URL`.

## Alternatives considered

- **Stay on Bootstrap 5, just write better templates.** Doesn't address the layout limitations or dark-mode gap; doesn't establish the component-helper convention either way.
- **Adopt Filament or Laravel Nova.** Both are framework-bound (Laravel) — would require rewriting routing, DI, and ORM. Out of scope.
- **TailAdmin React.** Would require a JS build pipeline + React runtime in production. Conflicts with deployment surface.
- **TailAdmin Pro.** Paid licence; v1 stays free.
- **Full Node + webpack pipeline mirroring upstream.** Adds a dependency that the project explicitly excluded. The standalone CLI captures the value at a fraction of the cost.

## References

- [TailAdmin Free Tailwind on GitHub](https://github.com/TailAdmin/tailadmin-free-tailwind-dashboard-template)
- ADR 0016 — English as default locale
- ADR 0011 — Flexible attributes and locations (M6-I; the Verification feature uses these)
