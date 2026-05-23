<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use saso\admin\AiSettingsDIContainer;
use Saso\Domain\Setting\SettingType;
use Saso\Domain\Setting\SettingValue;

/**
 * Locks down the helpers backing the "multiple API keys per provider"
 * feature in the AI Settings admin page: POST → normalised list (saved
 * as JSON), and stored value → list rendered back in the form.
 *
 * The container itself depends on $_SESSION / $_SERVER / header(), so
 * these helpers are exercised via Reflection rather than driving the
 * full di() entry point.
 */
final class AiSettingsKeyNormalizationTest extends TestCase
{
    /**
     * @param mixed $input
     *
     * @return list<string>
     */
    private function normalize($input): array
    {
        $method = new ReflectionMethod(AiSettingsDIContainer::class, 'normalizeKeys');
        $method->setAccessible(true);

        /** @var list<string> $result */
        $result = (array) $method->invoke(null, $input);

        return $result;
    }

    /**
     * @return list<string>
     */
    private function toKeyList(?SettingValue $value): array
    {
        $method = new ReflectionMethod(AiSettingsDIContainer::class, 'toKeyList');
        $method->setAccessible(true);

        /** @var list<string> $result */
        $result = (array) $method->invoke(null, $value);

        return $result;
    }

    public function testNormalizeReturnsEmptyForNonArrayInput(): void
    {
        self::assertSame([], $this->normalize(null));
        self::assertSame([], $this->normalize('not-an-array'));
        self::assertSame([], $this->normalize(42));
    }

    public function testNormalizeTrimsAndDropsEmpties(): void
    {
        $result = $this->normalize(['  sk-a  ', '', '   ', 'sk-b']);

        self::assertSame(['sk-a', 'sk-b'], $result);
    }

    public function testNormalizeDedupesPreservingFirstOccurrence(): void
    {
        $result = $this->normalize(['sk-a', 'sk-b', 'sk-a', 'sk-c', 'sk-b']);

        self::assertSame(['sk-a', 'sk-b', 'sk-c'], $result);
    }

    public function testNormalizeCapsAtTwentyKeys(): void
    {
        $input = [];
        for ($i = 0; $i < 50; ++$i) {
            $input[] = sprintf('sk-%03d', $i);
        }

        $result = $this->normalize($input);

        self::assertCount(20, $result);
        self::assertSame('sk-000', $result[0]);
        self::assertSame('sk-019', $result[19]);
    }

    public function testNormalizeSkipsNonStringEntries(): void
    {
        $result = $this->normalize(['sk-a', 42, null, ['nested'], 'sk-b']);

        self::assertSame(['sk-a', 'sk-b'], $result);
    }

    public function testToKeyListReturnsEmptyForNull(): void
    {
        self::assertSame([], $this->toKeyList(null));
    }

    public function testToKeyListReturnsArrayFromJsonArray(): void
    {
        $value = SettingValue::json(['sk-a', 'sk-b']);

        self::assertSame(['sk-a', 'sk-b'], $this->toKeyList($value));
    }

    public function testToKeyListDropsEmptyAndNonStringEntries(): void
    {
        $value = SettingValue::json(['sk-a', '', 'sk-b', null, 42]);

        self::assertSame(['sk-a', 'sk-b'], $this->toKeyList($value));
    }

    /**
     * Pre-multi-key data may exist as a single JSON-encoded string,
     * e.g. `"sk-only-one"` rather than `["sk-only-one"]`. Read paths
     * must lift that into a one-element list so the form still renders
     * correctly. Built via the raw constructor because the json()
     * factory rejects scalars; legacy rows on disk may still hold one.
     */
    public function testToKeyListWrapsLegacyJsonStringInArray(): void
    {
        $value = new SettingValue('"legacy-single-key"', SettingType::Json);

        self::assertSame(['legacy-single-key'], $this->toKeyList($value));
    }

    /**
     * Plain string SettingValues (not JSON-encoded) — extremely old
     * installations — should also lift to a one-element list, not
     * blow up the page with a JSON decode failure.
     */
    public function testToKeyListWrapsPlainStringSettingValue(): void
    {
        $value = SettingValue::string('plain-string-key');

        self::assertSame(['plain-string-key'], $this->toKeyList($value));
    }

    public function testToKeyListReturnsEmptyForBlankString(): void
    {
        $value = SettingValue::string('   ');

        self::assertSame([], $this->toKeyList($value));
    }
}
