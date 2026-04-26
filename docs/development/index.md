# Development

Welcome! Issues and pull requests are accepted in **English or Japanese**. This section covers the day-to-day developer loop. Higher-level expectations live in [`CONTRIBUTING.md`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/CONTRIBUTING.md).

| Page | What you'll find |
|---|---|
| [Workflow](workflow.md) | GitHub Flow, Conventional Commits, PR review expectations |
| [Testing](testing.md) | PHPUnit / PHPStan / PHP-CS-Fixer commands and scope |

## At a glance

```bash
make up           # build + start the local stack (app + db + adminer)
make install      # composer install inside the container
make qa           # cs-check + analyse + test
make shell        # bash inside the app container
```

`make help` lists every target.

## Code style

- **PSR-12** plus the additions in `.php-cs-fixer.dist.php` (short array syntax, ordered imports, single quotes, trailing commas).
- **PHPStan level 6** for everything in `src/`, `tests/`, and the M1 utility rewrites in `util/`. Legacy directories are excluded until they migrate.
- **Strict types**: every new PHP file under `src/` and `tests/` starts with `declare(strict_types=1);`.
- **No globals**: dependency injection via the existing `DIContainer` interface.

## Branching

Trunk-based with short-lived feature branches:

- `feat/<topic>` — new functionality
- `fix/<topic>` — bug or security fix
- `chore/<topic>` — tooling or repo housekeeping
- `docs/<topic>` — documentation only

Open the PR as a **draft** while work is in progress. Merge happens via squash with a Conventional Commits title.

## Next

- [Workflow](workflow.md)
- [Testing](testing.md)
- [Security policy](../security.md)
