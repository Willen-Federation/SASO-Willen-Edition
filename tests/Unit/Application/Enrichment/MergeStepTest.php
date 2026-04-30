<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Application\Enrichment;

use PHPUnit\Framework\TestCase;
use Saso\Application\Enrichment\Step\MergeStep;

final class MergeStepTest extends TestCase
{
    private MergeStep $step;

    protected function setUp(): void
    {
        $this->step = new MergeStep();
    }

    public function testOverlayFillsEmptyBaseKeys(): void
    {
        $result = $this->step->merge(
            base: ['item_name' => '', 'manufacturer' => null],
            overlay: ['item_name' => 'Widget', 'manufacturer' => 'Acme'],
            userProtected: [],
        );

        self::assertSame('Widget', $result['item_name']);
        self::assertSame('Acme', $result['manufacturer']);
    }

    public function testOverlayDoesNotOverwriteExistingValues(): void
    {
        $result = $this->step->merge(
            base: ['item_name' => 'Original'],
            overlay: ['item_name' => 'ShouldNotOverride'],
            userProtected: [],
        );

        self::assertSame('Original', $result['item_name']);
    }

    public function testUserProtectedKeysAreNeverOverwritten(): void
    {
        $result = $this->step->merge(
            base: ['item_name' => null, 'price' => null],
            overlay: ['item_name' => 'AI Name', 'price' => 999],
            userProtected: ['item_name'],
        );

        self::assertNull($result['item_name']);
        self::assertSame(999, $result['price']);
    }

    public function testEmptyArrayValuesInBaseGetFilled(): void
    {
        $result = $this->step->merge(
            base: ['tags' => []],
            overlay: ['tags' => ['a', 'b']],
            userProtected: [],
        );

        self::assertSame(['a', 'b'], $result['tags']);
    }

    public function testOverlayAddsNewKeys(): void
    {
        $result = $this->step->merge(
            base: ['item_name' => 'Widget'],
            overlay: ['category_hint' => 'Electronics'],
            userProtected: [],
        );

        self::assertSame('Widget', $result['item_name']);
        self::assertSame('Electronics', $result['category_hint']);
    }

    public function testEmptyOverlayReturnBase(): void
    {
        $base   = ['item_name' => 'Widget'];
        $result = $this->step->merge($base, [], []);

        self::assertSame($base, $result);
    }
}
