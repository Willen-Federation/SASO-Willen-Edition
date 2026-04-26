<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Plugin\Registry;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Auth\AuthenticatedIdentity;
use Saso\Domain\Auth\AuthProvider;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\AuthProviderType;
use Saso\Domain\Auth\CallbackRequest;
use Saso\Domain\Auth\LoginContext;
use Saso\Domain\Auth\LogoutContext;
use Saso\Domain\Auth\Redirect;
use Saso\Domain\Plugin\Registry\Exception\RegistryCollisionException;
use Saso\Domain\Plugin\Registry\RegistryName;
use Saso\Infrastructure\Plugin\Registry\InMemoryAuthProviderRegistry;

final class InMemoryAuthProviderRegistryTest extends TestCase
{
    public function testStartsEmpty(): void
    {
        $r = new InMemoryAuthProviderRegistry();

        self::assertSame([], $r->names());
        self::assertNull($r->get(new RegistryName('local')));
    }

    public function testRegisterCoreSeeds(): void
    {
        $r = new InMemoryAuthProviderRegistry();
        $r->registerCore(new RegistryName('local'), $this->fakeProvider());

        self::assertTrue($r->has(new RegistryName('local')));
    }

    public function testPluginVendorPrefixedNameIsAllowed(): void
    {
        $r = new InMemoryAuthProviderRegistry();
        $r->register(new RegistryName('acme:webauthn'), $this->fakeProvider());

        self::assertTrue($r->has(new RegistryName('acme:webauthn')));
    }

    public function testPluginCannotDisplaceReservedName(): void
    {
        $r = new InMemoryAuthProviderRegistry();
        $r->registerCore(new RegistryName('local'), $this->fakeProvider());

        $this->expectException(RegistryCollisionException::class);

        $r->register(new RegistryName('local'), $this->fakeProvider());
    }

    public function testReregisterOwnVendorNameOverwrites(): void
    {
        $r      = new InMemoryAuthProviderRegistry();
        $first  = $this->fakeProvider();
        $second = $this->fakeProvider();

        $r->register(new RegistryName('acme:webauthn'), $first);
        $r->register(new RegistryName('acme:webauthn'), $second);

        self::assertSame($second, $r->get(new RegistryName('acme:webauthn')));
    }

    private function fakeProvider(): AuthProvider
    {
        return new class () implements AuthProvider {
            public function id(): AuthProviderId
            {
                return new AuthProviderId(1);
            }

            public function type(): AuthProviderType
            {
                return AuthProviderType::Local;
            }

            public function displayName(): string
            {
                return 'Fake';
            }

            public function beginLogin(LoginContext $context): Redirect
            {
                return new Redirect('https://example.test/');
            }

            public function completeLogin(CallbackRequest $request): AuthenticatedIdentity
            {
                return new AuthenticatedIdentity(
                    authProviderId: new AuthProviderId(1),
                    externalSubject: 'sub',
                    email: 'x@y',
                    displayName: 'X',
                );
            }

            public function supportsLogout(): bool
            {
                return false;
            }

            public function beginLogout(LogoutContext $context): ?Redirect
            {
                return null;
            }
        };
    }
}
