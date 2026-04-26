<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Auth;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Auth\AuthProviderType;

final class AuthProviderTypeTest extends TestCase
{
    public function testValuesMatchDatabaseEnum(): void
    {
        self::assertSame('local', AuthProviderType::Local->value);
        self::assertSame('oidc', AuthProviderType::Oidc->value);
        self::assertSame('saml', AuthProviderType::Saml->value);
    }

    public function testFromString(): void
    {
        self::assertSame(AuthProviderType::Oidc, AuthProviderType::from('oidc'));
    }

    public function testCovers(): void
    {
        self::assertCount(3, AuthProviderType::cases());
    }
}
