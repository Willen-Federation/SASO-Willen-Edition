<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Feature;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Feature\Exception\FlagNotFoundException;
use Saso\Domain\Feature\FeatureKey;
use Saso\Domain\Shared\ErrorCode;

final class FlagNotFoundExceptionTest extends TestCase
{
    public function testCarriesFlagNotFoundCode(): void
    {
        $ex = FlagNotFoundException::for(new FeatureKey('checkout.new_flow'));

        self::assertSame(ErrorCode::FlagNotFound, $ex->errorCode());
        self::assertSame(404, $ex->errorCode()->httpStatus());
    }

    public function testCarriesKeyInContext(): void
    {
        $ex = FlagNotFoundException::for(new FeatureKey('checkout.new_flow'));

        self::assertSame('checkout.new_flow', $ex->context()['key']);
    }

    public function testMessageIncludesKey(): void
    {
        $ex = FlagNotFoundException::for(new FeatureKey('a.b'));

        self::assertStringContainsString('a.b', $ex->getMessage());
    }
}
