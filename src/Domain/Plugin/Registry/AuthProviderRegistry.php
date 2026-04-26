<?php

declare(strict_types=1);

namespace Saso\Domain\Plugin\Registry;

use Saso\Domain\Auth\AuthProvider;

/**
 * Plugin extension point for adding new {@see AuthProvider}
 * implementations beyond the core Local / OIDC / SAML set
 * (cf. ADR 0015 + ADR 0003).
 *
 * Plugins call {@see register()} from `Plugin::register()`. Names
 * follow the same reserved-vs-vendor convention as
 * {@see AiAssistantRegistry}: bare names (`local`, `oidc`, `saml`)
 * are core; vendor-prefixed names (`acme:webauthn`) are
 * plugin-owned. The login screen renders one button per registered
 * + enabled provider.
 */
interface AuthProviderRegistry
{
    /**
     * @throws \Saso\Domain\Plugin\Registry\Exception\RegistryCollisionException
     */
    public function register(RegistryName $name, AuthProvider $provider): void;

    public function unregister(RegistryName $name): void;

    public function get(RegistryName $name): ?AuthProvider;

    public function has(RegistryName $name): bool;

    /**
     * @return list<RegistryName>
     */
    public function names(): array;
}
