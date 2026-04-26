# Workflow

We use **GitHub Flow** with branch protection enforcing a PR-based merge. Every change — even from maintainers — flows through a review window so the diff stays auditable.

## Branch protection on `main`

| Rule | Setting |
|---|---|
| Require pull request before merging | yes |
| Required approving reviews | 0 (solo maintainer; raises with the team) |
| Dismiss stale reviews on push | yes |
| Required conversation resolution | yes |
| Linear history (squash-only merges) | yes |
| Force pushes | blocked |
| Branch deletion | blocked (auto-delete on merge handled separately) |
| Include administrators | no — solo maintainer can use `--admin` for the initial bootstrap. |

## Step-by-step

1. **Create a topic branch** from `main` (`git checkout -b feat/<topic>`).
2. **Write code + tests + docs**. Follow the patterns described in [Testing](testing.md) and [Architecture](../architecture/index.md).
3. **Open a draft PR** as soon as there is something to look at. Push as you go — CI runs on every push.
4. **Pass CI**: `php -l` matrix, JSON validation, composer-validate, PHP-CS-Fixer, PHPStan level 6, PHPUnit on PHP 8.2 / 8.3.
5. **Mark the PR ready for review.** The PR template's checklist needs to be honest — do not check items you have not actually verified.
6. **Squash-merge.** The merge commit title is the PR title (already in Conventional Commits format), and the body links the PR for future archaeology.

## Conventional Commits

Examples that have shipped:

- `feat(build): introduce Composer with PSR-4 src/ autoload bridge`
- `security(auth): migrate password hashing to Argon2id with lazy upgrade`
- `test(tooling): add PHPUnit / PHPStan / PHP-CS-Fixer with 52 unit tests`
- `docs: add MkDocs scaffold with bilingual EN/JA`
- `ci: drop noisy markdown-link-check`

Use the type that best describes the **primary** change. `chore` is the catch-all when nothing else fits, but try not to lean on it.

## Releases

SemVer with [release-please](https://github.com/googleapis/release-please) wired up in M5. Until then:

- The fork has no `v*` tags — pre-fork v2.4.0 history is documented in [`CHANGELOG.md`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/CHANGELOG.md).
- M5 cuts the first tagged release with a vendor-bundled ZIP attached.

## See also

- [`CONTRIBUTING.md`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/CONTRIBUTING.md)
- [`SECURITY.md`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/SECURITY.md)
- [Testing](testing.md)
