<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Plugin\Registry;

use Saso\Domain\Auth\AuthProvider;
use Saso\Domain\Plugin\Registry\AuthProviderRegistry;
use Saso\Domain\Plugin\Registry\Exception\RegistryCollisionException;
use Saso\Domain\Plugin\Registry\RegistryName;

/**
 * In-process {@see AuthProviderRegistry}.
 *
 * Same reserved-vs-vendor rules as {@see InMemoryAiAssistantRegistry}:
 * `register()` rejects collisions on reserved names; `registerCore()`
 * is the idempotent seeding path used by the composition root for
 * `local` / `oidc` / `saml`.
 */
final class InMemoryAuthProviderRegistry implements AuthProviderRegistry
{
    private const REGISTRY_LABEL = 'auth_provider';

    /** @var array<string, AuthProvider> */
    private array $byName = [];

    public function register(RegistryName $name, AuthProvider $provider): void
    {
        if ($name->isReserved() && isset($this->byName[$name->toString()])) {
            throw RegistryCollisionException::for(self::REGISTRY_LABEL, $name);
        }
        $this->byName[$name->toString()] = $provider;
    }

    public function registerCore(RegistryName $name, AuthProvider $provider): void
    {
        $this->byName[$name->toString()] = $provider;
    }

    public function unregister(RegistryName $name): void
    {
        unset($this->byName[$name->toString()]);
    }

    public function get(RegistryName $name): ?AuthProvider
    {
        return $this->byName[$name->toString()] ?? null;
    }

    public function has(RegistryName $name): bool
    {
        return isset($this->byName[$name->toString()]);
    }

    public function names(): array
    {
        $names = [];
        foreach (array_keys($this->byName) as $name) {
            $names[] = new RegistryName($name);
        }

        return $names;
    }
}
