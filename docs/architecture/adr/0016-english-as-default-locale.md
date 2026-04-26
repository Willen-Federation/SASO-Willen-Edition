# 0016 — Make the system fully usable in English (extract legacy JA strings, English as default)

* Status: accepted
* Date: 2026-04-26
* Deciders: @kackey621, Willen Federation contributors

## Context and Problem Statement

The legacy v2.4 templates and admin screens carry hard-coded Japanese strings (button labels, table headers, validation messages, PDF field captions). Since M3-C the i18n infrastructure exists (`symfony/translation` + `translations/en.yaml` + `translations/ja.yaml` + `LocaleResolver`) — but the catalogue only covers error codes; the UI itself is still untranslated.

Operators outside Japan installing SASO today see Japanese text in every admin screen until they translate the templates manually.

We need to:

1. extract every hard-coded JA string from legacy templates into the i18n catalogue;
2. make English the rendering default unless the request locale resolves to Japanese;
3. provide a locale switcher in the admin UI;
4. preserve the Japanese-language UX byte-for-byte for existing operators (no regressions).

## Decision Drivers

- **Global OSS positioning** — the project README and docs site already lead in English (M0); the running app must match.
- **No regression for JA operators** — every JA string the user sees today must remain intact when the locale resolves to `ja`.
- **Compose with M3-C** — extraction targets the existing `Translator` + `LocaleResolver`; no new i18n stack.
- **Strangler Fig (ADR 0001)** — extract per-template, not all-at-once. Each migrated template gets its strings under a stable translation namespace and a `__()` call (the helper M3-C registered).

## Considered Options

### Option A — Leave the system Japanese-only

* (−) Fails the user's explicit requirement.

### Option B — Inline `if ($locale === 'ja') ... else ...` branches in each template

* (+) Trivial to do per-string.
* (−) Re-implements i18n badly. Adding a third language means touching every template again.

### Option C — Extract to translation catalogues and call `__()` from templates

Every Japanese literal becomes a key in `translations/<locale>.yaml`. The default catalogue is English; the JA catalogue carries the legacy strings verbatim. Templates render via the global `__()` helper M3-C registered.

A one-shot migration script (`scripts/extract_legacy_strings.php`) walks the legacy templates, finds the JA literals, generates a key proposal (e.g. `legacy.item.list.title` for an `<h1>商品一覧</h1>`), and writes:

- the EN key with a placeholder English translation (operator-reviewed);
- the JA key with the existing string verbatim;
- a patch that replaces the literal with `<?= htmlspecialchars(__('legacy.item.list.title')) ?>`.

Operators review the patches, refine the English wording, and merge per template.

* (+) Survives a third locale with zero extra plumbing.
* (+) Keeps the hot-path templates working — extraction is incremental, one template per PR.
* (+) The English defaults are reviewable in the catalogue YAML, separately from the templates.

## Decision Outcome

**Chosen option: C — Catalogue-backed strings; English default; locale switcher in admin UI; per-template extraction PRs.**

### Default locale switch

`Saso\Presentation\Http\I18n\LocaleResolver` already orders sources as: `?lang=` → member preference → `Accept-Language` → configured default. The configured default flips to `en`. Existing JA operators are unaffected because:

- the M4-C `system_setting` row `default_locale` exists; if it is set to `ja` the resolver returns `ja` for any request that does not explicitly request another locale;
- newly installed instances default to `en` but the M5 installer prompts for the operator's preferred locale on first run.

### Locale switcher

A new admin-UI dropdown (top-right, next to the user menu) writes the chosen locale to `Member.locale` on the legacy `Member` table. The legacy `LoginUsecase` populates the column on first login from `Accept-Language`. The resolver reads it as the second source.

For non-authenticated pages (the login screen itself), the dropdown sets a `?lang=` query parameter — this is the resolver's first source.

### Translation namespacing

```
translations/en.yaml
  error: { … }              # M3-C: SASO-DOMAIN-NNNN catalogue (already exists)
  legacy:                   # M6-A2 onwards: extracted legacy strings
    item:
      list:
        title: 'Item list'
        empty: 'No items registered yet.'
    auth:
      login:
        button: 'Sign in'
  admin:                    # M6 onwards: new admin screens
    settings:
      heading: 'System settings'
```

The `legacy.*` namespace holds strings extracted from pre-M6 templates. New screens use other namespaces. Both share the same catalogue file.

### Extraction tooling

`scripts/extract_legacy_strings.php` is a one-shot helper, **not** part of the runtime. It:

1. recursively walks `template/` and `auth/`, `item/`, `category/`, `framework/` directories;
2. matches strings against a multibyte-aware regex (`/[\x{3040}-\x{30FF}\x{4E00}-\x{9FAF}]+/u`) — anything containing kana or kanji;
3. proposes a translation key using a heuristic (filename + heading text);
4. emits a patch file the operator applies via `git apply`.

The script is documented in `docs/development/i18n-extraction.md` (M6-A2 ships the page).

### Translation work distribution

- The fork-team translates the high-traffic screens (login, item list, item edit, admin settings, error pages) as part of M6-A3 — first feature PR after this ADR.
- Less-trafficked screens (rare admin tools, debug pages) get translated incrementally; until then they fall through to JA via the `LocaleResolver`'s fallback chain.
- A CI check (`scripts/audit_untranslated_strings.php`, M6-A3) walks the templates and fails the build if any new template introduces a JA literal without a `__()` call.

### PDF / printable assets

The legacy TCPDF label-printing module emits Japanese strings; M6-A3 keeps PDF labels JA-only by default and adds a `report.locale` system setting that operators can flip to `en`. PDF translations require fonts that contain both Latin and JA glyphs; the bundled `IPAex` font already does so the change is purely strings.

## Consequences

- New installs render in English by default, matching the project's global-OSS positioning.
- Existing JA installs are unaffected — `default_locale = ja` plus `Member.locale = ja` keeps every operator's UX byte-for-byte the same.
- Adding a third or fourth locale (zh, ko, …) requires no code changes — only a `<locale>.yaml` file.
- The legacy template tree gets a measurable migration target: `scripts/audit_untranslated_strings.php` reports a literal-count that goes to zero as M6-A3 onwards lands.
- The fork's release notes will spell out the locale-default change so operators can set `default_locale = ja` immediately if they prefer the old behaviour for new installs.
