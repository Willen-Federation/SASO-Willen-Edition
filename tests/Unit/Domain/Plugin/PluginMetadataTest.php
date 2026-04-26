<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Plugin;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Plugin\PluginMetadata;

final class PluginMetadataTest extends TestCase
{
    public function testStoresFields(): void
    {
        $m = new PluginMetadata(
            package: 'willen-federation/saso-plugin-foo',
            name: 'Plugin foo',
            version: '1.0.0',
            versionCompat: '^1.0',
        );

        self::assertSame('willen-federation/saso-plugin-foo', $m->package);
        self::assertSame('Plugin foo', $m->name);
        self::assertSame('1.0.0', $m->version);
        self::assertSame('^1.0', $m->versionCompat);
    }

    public function testRejectsEmptyPackage(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PluginMetadata(package: '', name: 'n', version: '1');
    }

    public function testRejectsPackageWithoutSlash(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('vendor/name');

        new PluginMetadata(package: 'no-slash', name: 'n', version: '1');
    }

    public function testRejectsEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PluginMetadata(package: 'a/b', name: '', version: '1');
    }

    public function testRejectsEmptyVersion(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PluginMetadata(package: 'a/b', name: 'n', version: '');
    }

    public function testDefaultVersionCompatIsWildcard(): void
    {
        $m = new PluginMetadata(package: 'a/b', name: 'n', version: '1');

        self::assertSame('*', $m->versionCompat);
    }
}
