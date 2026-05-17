<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Barcode;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Barcode\BarcodeCode;

final class BarcodeCodeTest extends TestCase
{
    public function testCreatesDefaultPendingCodeFromSequence(): void
    {
        self::assertSame('PND000000007', BarcodeCode::fromSequence(7)->asString());
    }

    public function testCreatesCustomPrefixedCodeFromSequence(): void
    {
        self::assertSame('BC00042', BarcodeCode::fromSequence(42, 'bc', 5)->asString());
    }

    public function testNormalizesInvalidPrefixToDefault(): void
    {
        self::assertSame('PND0001', BarcodeCode::fromSequence(1, '123', 4)->asString());
    }

    public function testAcceptsOneCharacterPrefix(): void
    {
        self::assertSame('A0001', BarcodeCode::fromSequence(1, 'a', 4)->asString());
    }

    public function testRejectsLegacyNumericCode(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BarcodeCode('123456789012');
    }

    public function testCreatesJanCodeWithCheckDigit(): void
    {
        self::assertSame('4900000000016', BarcodeCode::fromJanSequence(1, '49')->asString());
    }

    public function testAcceptsThirteenDigitJanCode(): void
    {
        self::assertSame('4900000000016', (new BarcodeCode('4900000000016'))->asString());
    }
}
