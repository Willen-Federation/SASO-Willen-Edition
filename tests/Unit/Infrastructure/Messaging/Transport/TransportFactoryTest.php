<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Messaging\Transport;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Messaging\Message\IndexItem;
use Saso\Domain\Messaging\Message\ProcessItemDraft;
use Saso\Infrastructure\Messaging\Transport\AllowlistedClassesSerializer;
use Saso\Infrastructure\Messaging\Transport\TransportFactory;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

final class TransportFactoryTest extends TestCase
{
    public function testCreateSerializerReturnsAllowlistedSerializer(): void
    {
        self::assertInstanceOf(
            AllowlistedClassesSerializer::class,
            TransportFactory::createSerializer(),
        );
    }

    public function testSerializerAcceptsKnownDomainMessages(): void
    {
        $serializer = TransportFactory::createSerializer();
        $inner      = new PhpSerializer();

        $indexItem = $inner->encode(new Envelope(new IndexItem(itemId: 1)));
        $draft     = $inner->encode(new Envelope(new ProcessItemDraft(2)));

        self::assertInstanceOf(IndexItem::class, $serializer->decode($indexItem)->getMessage());
        self::assertInstanceOf(ProcessItemDraft::class, $serializer->decode($draft)->getMessage());
    }

    public function testSerializerRejectsForeignClass(): void
    {
        $serializer = TransportFactory::createSerializer();
        $inner      = new PhpSerializer();

        // \stdClass stands in for any "non-message" object an attacker
        // could plant in the messenger_messages row.
        $encoded = $inner->encode(new Envelope(new \stdClass()));

        $this->expectException(MessageDecodingFailedException::class);

        $serializer->decode($encoded);
    }
}
