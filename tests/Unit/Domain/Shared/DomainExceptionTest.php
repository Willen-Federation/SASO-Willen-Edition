<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Shared;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

final class DomainExceptionTest extends TestCase
{
    public function testCarriesErrorCodeAndContext(): void
    {
        $ex = new class (ErrorCode::AuthInvalidCredentials, 'bad password', ['user_id' => 42]) extends DomainException {};

        self::assertSame(ErrorCode::AuthInvalidCredentials, $ex->errorCode());
        self::assertSame('bad password', $ex->getMessage());
        self::assertSame(['user_id' => 42], $ex->context());
    }

    public function testEmptyMessageFallsBackToDefaultTitle(): void
    {
        $ex = new class (ErrorCode::AuthSessionExpired) extends DomainException {};

        self::assertSame('Session expired', $ex->getMessage());
    }

    public function testPreservesPreviousException(): void
    {
        $previous = new RuntimeException('underlying');
        $ex       = new class (ErrorCode::InfraDatabaseUnavailable, 'db down', [], $previous) extends DomainException {};

        self::assertSame($previous, $ex->getPrevious());
    }

    public function testEmptyContextDefault(): void
    {
        $ex = new class (ErrorCode::AuthForbidden) extends DomainException {};

        self::assertSame([], $ex->context());
    }
}
