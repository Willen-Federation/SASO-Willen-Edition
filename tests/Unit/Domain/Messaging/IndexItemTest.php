<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Messaging;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Messaging\Message\IndexItem;
use Saso\Domain\Messaging\Message\Message;

final class IndexItemTest extends TestCase
{
    public function testStoresFields(): void
    {
        $m = new IndexItem(itemId: 42, reason: 'manual reindex');

        self::assertSame(42, $m->itemId);
        self::assertSame('manual reindex', $m->reason);
    }

    public function testDefaultReason(): void
    {
        $m = new IndexItem(itemId: 1);

        self::assertSame('item-write', $m->reason);
    }

    public function testIsAMessage(): void
    {
        self::assertInstanceOf(Message::class, new IndexItem(1));
    }

    public function testRejectsNonPositiveItemId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new IndexItem(0);
    }

    public function testRejectsEmptyReason(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new IndexItem(itemId: 1, reason: '');
    }

    public function testIsSerialisable(): void
    {
        $m   = new IndexItem(itemId: 99, reason: 'post-import');
        $rt  = unserialize(serialize($m));

        self::assertEquals($m, $rt);
    }
}
