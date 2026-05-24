<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\SettingAdmin;

use PHPUnit\Framework\TestCase;

/**
 * Pins down structural invariants of the shelf-settings admin template.
 *
 * The framework's UserCompiler enforces a CSRF check on every authed
 * POST and `die()`s when the `csrftoken` field is missing — without an
 * embedded token the form simply cannot submit. The shelf-settings
 * template historically omitted the hidden input, so the page rendered
 * fine but the "Save Configuration" button was a dead end.
 */
final class ShelfSettingsTemplateTest extends TestCase
{
    private const TEMPLATE_PATH = __DIR__ . '/../../../settingAdmin/template/shelf-settings.php';

    public function testTemplateExists(): void
    {
        self::assertFileExists(self::TEMPLATE_PATH);
    }

    public function testFormIncludesCsrfTokenHiddenInput(): void
    {
        $source = (string) file_get_contents(self::TEMPLATE_PATH);

        self::assertStringContainsString(
            'name="csrftoken"',
            $source,
            'shelf-settings form must include the csrftoken hidden input.',
        );
        self::assertStringContainsString(
            '\\saso\\util\\CSRFtoken::current()',
            $source,
            'shelf-settings form must source the token from CSRFtoken::current().',
        );
    }
}
