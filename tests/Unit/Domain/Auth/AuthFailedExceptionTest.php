<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Auth;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Auth\Exception\AuthFailedException;
use Saso\Domain\Auth\Exception\ProviderMisconfiguredException;
use Saso\Domain\Shared\ErrorCode;

final class AuthFailedExceptionTest extends TestCase
{
    public function testInvalidCredentialsCarriesAuth1001(): void
    {
        $ex = AuthFailedException::invalidCredentials('wrong password');

        self::assertSame(ErrorCode::AuthInvalidCredentials, $ex->errorCode());
        self::assertSame(401, $ex->errorCode()->httpStatus());
        self::assertSame('wrong password', $ex->getMessage());
    }

    public function testStateMismatchCarriesAuth1007(): void
    {
        $ex = AuthFailedException::stateMismatch();

        self::assertSame(ErrorCode::AuthCallbackStateMismatch, $ex->errorCode());
        self::assertSame(400, $ex->errorCode()->httpStatus());
    }

    public function testCallbackInvalidCarriesAuth1008(): void
    {
        $ex = AuthFailedException::callbackInvalid('signature did not validate');

        self::assertSame(ErrorCode::AuthCallbackValidationFailed, $ex->errorCode());
        self::assertSame('signature did not validate', $ex->getMessage());
    }

    public function testProviderMisconfiguredCarriesAuth1006AndContext(): void
    {
        $ex = ProviderMisconfiguredException::for('Auth0 (test)', 'discovery URL not reachable');

        self::assertSame(ErrorCode::AuthProviderMisconfigured, $ex->errorCode());
        self::assertSame(503, $ex->errorCode()->httpStatus());
        self::assertSame('Auth0 (test)', $ex->context()['provider']);
        self::assertSame('discovery URL not reachable', $ex->context()['reason']);
    }
}
