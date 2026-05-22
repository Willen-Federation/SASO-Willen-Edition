<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Application\MyPage\Passkey;

use PHPUnit\Framework\TestCase;
use Saso\Application\MyPage\Passkey\Auth0PasskeyConfig;

final class Auth0PasskeyConfigTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $envBackup = [];

    protected function setUp(): void
    {
        foreach (['AUTH0_M2M_DOMAIN', 'AUTH0_M2M_CLIENT_ID', 'AUTH0_M2M_CLIENT_SECRET'] as $key) {
            $this->envBackup[$key] = getenv($key);
            putenv($key);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->envBackup as $key => $value) {
            if ($value === false) {
                putenv($key);
            } else {
                putenv("$key=$value");
            }
        }
    }

    public function testReturnsNullWhenAllEnvVarsMissing(): void
    {
        self::assertNull(Auth0PasskeyConfig::fromEnv());
    }

    public function testReturnsNullWhenSecretMissing(): void
    {
        putenv('AUTH0_M2M_DOMAIN=tenant.auth0.com');
        putenv('AUTH0_M2M_CLIENT_ID=abc');
        self::assertNull(Auth0PasskeyConfig::fromEnv());
    }

    public function testBuildsConfigWhenAllSet(): void
    {
        putenv('AUTH0_M2M_DOMAIN=tenant.auth0.com');
        putenv('AUTH0_M2M_CLIENT_ID=abc');
        putenv('AUTH0_M2M_CLIENT_SECRET=shh');

        $config = Auth0PasskeyConfig::fromEnv();

        self::assertNotNull($config);
        self::assertSame('tenant.auth0.com', $config->domain);
        self::assertSame('abc', $config->clientId);
        self::assertSame('shh', $config->clientSecret);
    }

    public function testFallsBackToProvidedDomainWhenEnvMissing(): void
    {
        putenv('AUTH0_M2M_CLIENT_ID=abc');
        putenv('AUTH0_M2M_CLIENT_SECRET=shh');

        $config = Auth0PasskeyConfig::fromEnv('linked.auth0.com');

        self::assertNotNull($config);
        self::assertSame('linked.auth0.com', $config->domain);
    }

    public function testEnvDomainWinsOverFallback(): void
    {
        putenv('AUTH0_M2M_DOMAIN=primary.auth0.com');
        putenv('AUTH0_M2M_CLIENT_ID=abc');
        putenv('AUTH0_M2M_CLIENT_SECRET=shh');

        $config = Auth0PasskeyConfig::fromEnv('fallback.auth0.com');

        self::assertNotNull($config);
        self::assertSame('primary.auth0.com', $config->domain);
    }
}
