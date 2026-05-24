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

    public function testRejectsJanSequenceThatOverflowsPrefixWidth(): void
    {
        // Prefix "49" leaves 10 digits for the sequence; 10_000_000_000
        // needs 11 digits. The old behaviour wrapped this into the EAN-13
        // check-digit helper, which threw on a 13-digit body — fine, but
        // the exception was misleading. fromJanSequence now rejects it up
        // front so callers see the real cause.
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not fit');

        BarcodeCode::fromJanSequence(10_000_000_000, '49');
    }

    public function testJanSequenceFitsExactlyAtMaxWidth(): void
    {
        // Prefix "49" + 10-digit sequence + check digit = 13 digits.
        $code = BarcodeCode::fromJanSequence(9_999_999_999, '49');
        self::assertSame(13, strlen($code->asString()));
        self::assertStringStartsWith('4999999999', $code->asString());
    }
}
