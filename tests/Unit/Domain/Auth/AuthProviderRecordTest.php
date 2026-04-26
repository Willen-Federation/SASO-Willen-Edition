<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Auth;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\AuthProviderRecord;
use Saso\Domain\Auth\AuthProviderType;

final class AuthProviderRecordTest extends TestCase
{
    public function testStoresEveryField(): void
    {
        $now = new DateTimeImmutable('2026-04-26 12:00:00');
        $r   = new AuthProviderRecord(
            id: new AuthProviderId(1),
            name: 'Auth0 (staff)',
            type: AuthProviderType::Oidc,
            issuerOrMetadataUrl: 'https://staff.example.auth0.com/.well-known/openid-configuration',
            clientId: 'abc123',
            clientSecret: 'shh',
            scopes: 'openid email profile',
            claimMapping: ['display_name' => 'preferred_username'],
            enabled: true,
            isDefault: true,
            createdAt: $now,
            updatedAt: $now,
        );

        self::assertSame(1, $r->id->value);
        self::assertSame('Auth0 (staff)', $r->name);
        self::assertSame(AuthProviderType::Oidc, $r->type);
        self::assertSame('shh', $r->clientSecret);
        self::assertSame(['display_name' => 'preferred_username'], $r->claimMapping);
        self::assertTrue($r->enabled);
        self::assertTrue($r->isDefault);
    }

    public function testWithEnabledReturnsCopyWithFlagFlipped(): void
    {
        $now = new DateTimeImmutable('2026-04-26 12:00:00');
        $r   = new AuthProviderRecord(
            id: new AuthProviderId(1),
            name: 'X',
            type: AuthProviderType::Local,
            issuerOrMetadataUrl: null,
            clientId: null,
            clientSecret: null,
            scopes: null,
            claimMapping: null,
            enabled: true,
            isDefault: false,
            createdAt: $now,
            updatedAt: $now,
        );

        $disabled = $r->withEnabled(false);

        self::assertNotSame($r, $disabled);
        self::assertTrue($r->enabled);
        self::assertFalse($disabled->enabled);
        self::assertSame($r->id, $disabled->id);
    }
}
