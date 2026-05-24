<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Messaging\Transport;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Messaging\Message\IndexItem;
use Saso\Domain\Messaging\Message\ProcessItemDraft;
use Saso\Infrastructure\Messaging\Transport\AllowlistedClassesSerializer;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

final class AllowlistedClassesSerializerTest extends TestCase
{
    public function testDecodesAllowedMessageClass(): void
    {
        $inner      = new PhpSerializer();
        $serializer = new AllowlistedClassesSerializer($inner, [IndexItem::class]);

        $encoded  = $inner->encode(new Envelope(new IndexItem(itemId: 7, reason: 'unit')));
        $envelope = $serializer->decode($encoded);

        self::assertInstanceOf(IndexItem::class, $envelope->getMessage());
    }

    public function testRejectsMessageClassOutsideAllowlist(): void
    {
        $inner      = new PhpSerializer();
        $serializer = new AllowlistedClassesSerializer($inner, [IndexItem::class]);

        $encoded = $inner->encode(new Envelope(new ProcessItemDraft(7)));

        $this->expectException(MessageDecodingFailedException::class);
        $this->expectExceptionMessageMatches('/ProcessItemDraft.*not in.*allowlist/');

        $serializer->decode($encoded);
    }

    public function testEncodeDelegatesToInner(): void
    {
        $inner      = new PhpSerializer();
        $serializer = new AllowlistedClassesSerializer($inner, [IndexItem::class]);

        $envelope = new Envelope(new IndexItem(itemId: 1));

        self::assertSame(
            $inner->encode($envelope),
            $serializer->encode($envelope),
        );
    }

    public function testEmptyAllowlistRejectsEverything(): void
    {
        $inner      = new PhpSerializer();
        $serializer = new AllowlistedClassesSerializer($inner, []);

        $encoded = $inner->encode(new Envelope(new IndexItem(itemId: 1)));

        $this->expectException(MessageDecodingFailedException::class);

        $serializer->decode($encoded);
    }
}
