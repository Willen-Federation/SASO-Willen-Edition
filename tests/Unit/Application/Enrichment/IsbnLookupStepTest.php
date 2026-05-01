<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Application\Enrichment;

use PHPUnit\Framework\TestCase;
use Saso\Application\Enrichment\Step\IsbnLookupStep;

final class IsbnLookupStepTest extends TestCase
{
    private IsbnLookupStep $step;

    protected function setUp(): void
    {
        $this->step = new IsbnLookupStep();
    }

    public function testNullBarcodeReturnsEmpty(): void
    {
        self::assertSame([], $this->step->run(null));
    }

    public function testNonIsbnJanCodeReturnsEmpty(): void
    {
        self::assertSame([], $this->step->run('4901234567890')); // JAN, not ISBN
    }

    public function testShortCodeReturnsEmpty(): void
    {
        self::assertSame([], $this->step->run('978'));
    }

    public function testNonDigitCodeReturnsEmpty(): void
    {
        self::assertSame([], $this->step->run('978ABCDEFGHIJ'));
    }

    public function testEightDigitCodeReturnsEmpty(): void
    {
        self::assertSame([], $this->step->run('12345678'));
    }
}
