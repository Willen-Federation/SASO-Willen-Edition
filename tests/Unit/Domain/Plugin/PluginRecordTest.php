<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Plugin;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Plugin\PluginRecord;

final class PluginRecordTest extends TestCase
{
    public function testStoresFields(): void
    {
        $now = new DateTimeImmutable('2026-04-27 10:00:00');
        $r   = $this->make(activated: $now);

        self::assertSame(1, $r->id);
        self::assertSame('willen-federation/saso-plugin-foo', $r->package);
        self::assertSame($now, $r->activatedAt);
        self::assertNull($r->deactivatedAt);
        self::assertNull($r->lastSeenAt);
        self::assertTrue($r->isActive());
    }

    public function testRejectsNonPositiveId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->make(id: 0);
    }

    public function testRejectsEmptyPackage(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->make(package: '');
    }

    public function testRejectsPackageWithoutSlash(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->make(package: 'no-slash');
    }

    public function testIsActiveFlipsOnDeactivation(): void
    {
        $r = $this->make();
        self::assertTrue($r->isActive());

        $deactivated = $r->withDeactivatedAt(new DateTimeImmutable('2026-04-27 12:00:00'));
        self::assertFalse($deactivated->isActive());
        self::assertNotSame($r, $deactivated);
    }

    public function testWithLastSeenAtIsNonMutating(): void
    {
        $r    = $this->make();
        $now  = new DateTimeImmutable('2026-04-27 12:00:00');
        $seen = $r->withLastSeenAt($now);

        self::assertNull($r->lastSeenAt);
        self::assertSame($now, $seen->lastSeenAt);
        self::assertNotSame($r, $seen);
    }

    private function make(
        int $id = 1,
        string $package = 'willen-federation/saso-plugin-foo',
        ?DateTimeImmutable $activated = null,
    ): PluginRecord {
        return new PluginRecord(
            id: $id,
            package: $package,
            class: 'Acme\\Foo\\Plugin',
            name: 'Plugin foo',
            version: '1.0.0',
            activatedAt: $activated ?? new DateTimeImmutable('2026-04-27 10:00:00'),
            deactivatedAt: null,
            lastSeenAt: null,
            settings: null,
        );
    }
}
