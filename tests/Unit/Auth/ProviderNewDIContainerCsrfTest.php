<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use saso\auth\ProviderNewDIContainer;
use saso\common\EmptyUsecase;
use saso\util\CSRFtoken;

/**
 * Verifies that the legacy provider-creation wizard rejects POSTs whose
 * `csrftoken` does not match the current session token.
 *
 * The wizard runs both pre-login (first-boot, no admin yet) and post-login.
 * UserCompiler only invokes the global CSRF check when $authed is true, so
 * the pre-login submit would otherwise be unchecked. The DI container has to
 * enforce the token locally.
 */
final class ProviderNewDIContainerCsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testMissingCsrfRoutesToEmptyUsecase(): void
    {
        // Pre-seed a session token so CSRFtoken::verify('') returns false
        // instead of generating a token at verify-time.
        CSRFtoken::current();

        $container = new ProviderNewDIContainer();
        $container->di(
            static fn () => null,
            [],
            [
                'provider_template' => 'auth0',
                'provider_name'     => 'Auth0',
                'auth0_domain'      => 'example.auth0.com',
            ],
            [],
            new \DateTime(),
        );

        self::assertInstanceOf(EmptyUsecase::class, $this->extractUsecase($container));
    }

    public function testWrongCsrfRoutesToEmptyUsecase(): void
    {
        CSRFtoken::current();

        $container = new ProviderNewDIContainer();
        $container->di(
            static fn () => null,
            [],
            [
                'csrftoken'         => str_repeat('a', 64),
                'provider_template' => 'auth0',
                'provider_name'     => 'Auth0',
                'auth0_domain'      => 'example.auth0.com',
            ],
            [],
            new \DateTime(),
        );

        self::assertInstanceOf(EmptyUsecase::class, $this->extractUsecase($container));
    }

    private function extractUsecase(ProviderNewDIContainer $container): mixed
    {
        $reflector = new \ReflectionClass($container);
        $prop = $reflector->getProperty('usecase');
        $prop->setAccessible(true);
        return $prop->getValue($container);
    }
}
