<?php

declare(strict_types=1);

namespace Saso\Domain\Plugin;

use InvalidArgumentException;

/**
 * Self-description a {@see Plugin} returns from
 * {@see Plugin::metadata()}.
 *
 * The Composer package name is the canonical identifier — operators
 * `composer require` and `composer remove` against it. The
 * `versionCompat` constraint is checked against the running SASO core
 * version at discovery time; mismatches are logged and the plugin is
 * skipped.
 */
final readonly class PluginMetadata
{
    public function __construct(
        public string $package,
        public string $name,
        public string $version,
        public string $versionCompat = '*',
    ) {
        if ($package === '') {
            throw new InvalidArgumentException('PluginMetadata.package must not be empty.');
        }
        if (!str_contains($package, '/')) {
            throw new InvalidArgumentException(sprintf(
                'PluginMetadata.package must be a Composer-style "vendor/name" (got %s).',
                $package,
            ));
        }
        if ($name === '') {
            throw new InvalidArgumentException('PluginMetadata.name must not be empty.');
        }
        if ($version === '') {
            throw new InvalidArgumentException('PluginMetadata.version must not be empty.');
        }
    }
}
