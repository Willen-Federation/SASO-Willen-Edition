<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Auth\Provider;

use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\AuthProviderRecord;
use Saso\Domain\Auth\AuthProviderType;
use Saso\Domain\Auth\LoginContext;
use Saso\Infrastructure\Auth\Provider\LocalProvider;

final class LocalProviderTest extends TestCase
{
    public function testBeginLoginUsesAbsoluteLoginPath(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $provider = new LocalProvider(
            new AuthProviderRecord(
                id: new AuthProviderId(1),
                name: 'Local Login',
                type: AuthProviderType::Local,
                issuerOrMetadataUrl: null,
                clientId: null,
                clientSecret: null,
                scopes: null,
                claimMapping: null,
                enabled: true,
                isDefault: true,
                createdAt: new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
                updatedAt: new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            ),
            new PDO('sqlite::memory:'),
        );

        $redirect = $provider->beginLogin(new LoginContext('/', 'state', 'nonce'));

        self::assertSame('/auth/start', $redirect->url);
        self::assertSame(302, $redirect->status);
    }
}
