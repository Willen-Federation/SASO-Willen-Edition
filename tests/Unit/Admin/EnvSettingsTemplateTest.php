<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;

/**
 * Locks down sensitive-value handling in the ENV Settings admin template.
 *
 * The placeholder for APP_KEY used to invoke a `$mask()` helper that
 * concatenated bullets with the *last 4 characters of the real key*.
 * That leaked partial key material into the rendered HTML attribute and
 * narrowed the brute-force search space for anyone who could view the
 * admin page source. Lock the template to a constant bullet placeholder
 * so the secret value never round-trips into the response body.
 */
final class EnvSettingsTemplateTest extends TestCase
{
    private const TEMPLATE_PATH = __DIR__.'/../../../admin/template/env-settings.php';

    public function testTemplateExists(): void
    {
        self::assertFileExists(self::TEMPLATE_PATH);
    }

    public function testTemplateDoesNotInvokeMaskHelperOnAppKey(): void
    {
        $source = (string) file_get_contents(self::TEMPLATE_PATH);

        // The pre-fix template defined `$mask = function (string $val)`
        // and called `$mask((string)($env['APP_KEY'] ?? ''))` inside the
        // placeholder attribute, which embedded the last 4 characters of
        // the live APP_KEY into the rendered HTML. The template must not
        // re-introduce either piece of that pattern.
        self::assertStringNotContainsString(
            '$mask',
            $source,
            'env-settings.php must not invoke a $mask helper that leaks APP_KEY chars.',
        );
        self::assertStringNotContainsString(
            'substr($val, -4)',
            $source,
            'env-settings.php must not slice the tail of any secret value.',
        );
    }

    public function testAppKeyPlaceholderUsesConstantBullets(): void
    {
        $source = (string) file_get_contents(self::TEMPLATE_PATH);

        // The replacement placeholder is a fixed bullet string regardless
        // of the actual APP_KEY length, so nothing about the secret leaks
        // through the attribute value.
        self::assertMatchesRegularExpression(
            "/placeholder=\"<\\?php echo \\(\\\$env\\['APP_KEY'\\] \\?\\? ''\\) !== '' \\? '•+' : '[^']*'; \\?>\"/u",
            $source,
            'APP_KEY placeholder must echo a constant bullet string for non-empty values.',
        );
    }
}
