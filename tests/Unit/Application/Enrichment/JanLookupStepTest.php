<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Application\Enrichment;

use PHPUnit\Framework\TestCase;
use Saso\Application\Enrichment\Step\JanLookupStep;

final class JanLookupStepTest extends TestCase
{
    private JanLookupStep $step;

    protected function setUp(): void
    {
        $this->step = new JanLookupStep();
    }

    public function testNullReturnsEmpty(): void
    {
        self::assertSame([], $this->step->run(null));
    }

    public function testIsbn13CodeReturnsEmpty(): void
    {
        self::assertSame([], $this->step->run('9780140449136')); // ISBN, not JAN
    }

    public function testIsbn979CodeReturnsEmpty(): void
    {
        self::assertSame([], $this->step->run('9791032308943'));
    }

    public function testNonDigitReturnsEmpty(): void
    {
        self::assertSame([], $this->step->run('490ABCDE12345'));
    }

    public function testTwelveDigitReturnsEmpty(): void
    {
        self::assertSame([], $this->step->run('123456789012')); // 12 digits — not 8 or 13
    }

    public function testNineDigitReturnsEmpty(): void
    {
        self::assertSame([], $this->step->run('123456789'));
    }
}
