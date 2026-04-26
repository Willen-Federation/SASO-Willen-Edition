<?php

declare(strict_types=1);

namespace Saso\Domain\Plugin\Registry\Exception;

use Saso\Domain\Plugin\Registry\RegistryName;
use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

/**
 * Thrown when a plugin attempts to register an entry under a
 * reserved (core) name in one of the typed plugin registries
 * (cf. ADR 0015).
 *
 * Plugins owning their entries (vendor-prefixed names) may freely
 * overwrite their own registrations — only collisions with core
 * names raise this exception.
 */
final class RegistryCollisionException extends DomainException
{
    public static function for(string $registry, RegistryName $name): self
    {
        return new self(
            ErrorCode::PluginRegistryCollision,
            sprintf(
                'Plugin attempted to register name "%s" in registry "%s", but the name is reserved for core use.',
                $name->toString(),
                $registry,
            ),
            ['registry' => $registry, 'name' => $name->toString()],
        );
    }
}
