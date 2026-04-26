# Testing

The QA pipeline ships in **M2-B**. Every PR runs:

| Check | Tool | Scope |
|---|---|---|
| PHP syntax | `php -l` | every `*.php` outside `vendor/` and `extention/TCPDF/` |
| Code style | `php-cs-fixer` | `src/`, `tests/`, the three M1 utility rewrites |
| Static analysis | `phpstan` (level 6) | same as code style |
| Unit tests | `phpunit` | `tests/Unit/**` on PHP 8.2 + 8.3 |

Legacy directories (`auth/`, `item/`, `framework/`, `repository/`, …) are deliberately excluded from the lint/analyse scope — they migrate to `src/` across M3-M4 and will be re-included with strict types in tow.

## Running locally

```bash
# Inside the dev container (preferred):
make qa            # cs-check + analyse + test
make test          # unit suite only
make analyse       # PHPStan
make cs-check      # dry-run formatter

# On the host (requires `composer install`):
composer test
composer analyse
composer cs:check
composer cs:fix
```

## Suites

```
tests/
├── bootstrap.php        registers the legacy `saso\` autoloader
├── Unit/                pure functions / no I/O
├── Integration/         uses real MariaDB (created when M3 lands)
└── Feature/             end-to-end via Playwright (planned for M5)
```

### What we test today (M2-B baseline — 52 cases)

- `Either` monad — `of`, `left`, `fromNullable` quirks, `map` / `flatMap` propagation, `filter`, `getOrElseThrow`, `orElse`.
- `Member` — Argon2id format & non-determinism, verifyPassword for both modern and legacy hashes, `needsRehash` truth table, `idConstraint`, `passwordConstraint`.
- `CSRFtoken` — 64-hex-char generation, intra-session stability, `rotate()`, `hash_equals` verify, post-rotate rejection.
- `EnvLoader` — KEY=VALUE parsing, comments, single/double quote stripping, malformed-key rejection, `getenv()` precedence, default fallback, empty-vs-unset distinction.
- `UploadValidator` — pre-`is_uploaded_file` checks. Post-SAPI flow (`finfo_file`, `getimagesize`) waits for the integration suite in M5.

### Coverage targets

- **Domain**: ≥ 80%
- **Application**: ≥ 60%
- **Overall**: ≥ 50%

These ratchets are advisory until M3 publishes the first ADR formalizing them.

## Writing new tests

```php
<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\YourNamespace;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Saso\YourNamespace\YourClass::class)]
final class YourClassTest extends TestCase
{
    public function testItDoesTheThing(): void
    {
        // Arrange / Act / Assert
        self::assertTrue(true);
    }
}
```

Conventions:

- One test class per production class (`#[CoversClass]`).
- Method names start with `test`. `it` and `should` styles are also fine.
- Prefer `self::assertSame` over `self::assertEquals` (strict equality).
- Mocking the database is **not** allowed for integration / feature suites — see ADR 0007 (planned) for the reasoning.

## See also

- [`phpunit.xml.dist`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/phpunit.xml.dist)
- [`phpstan.neon.dist`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/phpstan.neon.dist)
- [`.php-cs-fixer.dist.php`](https://github.com/Willen-Federation/SASO-Willen-Edition/blob/main/.php-cs-fixer.dist.php)
