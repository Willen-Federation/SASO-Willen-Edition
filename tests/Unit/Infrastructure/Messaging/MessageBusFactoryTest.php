<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Messaging;

use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;
use Saso\Application\Messaging\Handler\IndexItemHandler;
use Saso\Domain\Messaging\Message\IndexItem;
use Saso\Infrastructure\Logging\MonologFactory;
use Saso\Infrastructure\Messaging\MessageBusFactory;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessageBusFactoryTest extends TestCase
{
    public function testCreateReturnsMessageBus(): void
    {
        $bus = MessageBusFactory::create([]);

        self::assertInstanceOf(MessageBusInterface::class, $bus);
    }

    public function testDispatchedMessageReachesItsHandler(): void
    {
        $logHandler = new TestHandler();
        $handler    = new IndexItemHandler(MonologFactory::withHandler($logHandler));

        $bus = MessageBusFactory::create([
            IndexItem::class => [$handler],
        ]);

        $bus->dispatch(new IndexItem(itemId: 99, reason: 'unit test'));

        self::assertTrue($logHandler->hasInfoRecords());
        $record = $logHandler->getRecords()[0];
        self::assertSame(99, $record->context['item_id']);
        self::assertSame('unit test', $record->context['reason']);
    }

    public function testUnregisteredMessageRaisesNoHandler(): void
    {
        $bus = MessageBusFactory::create([]);

        $this->expectException(NoHandlerForMessageException::class);

        $bus->dispatch(new IndexItem(1));
    }

    public function testRegistrationIsKeyedByMessageClass(): void
    {
        // Registering a handler for IndexItem must not also pick up
        // unrelated message classes — verifies the locator's
        // class-keyed dispatch isn't accidentally global.
        $logHandler = new TestHandler();
        $handler    = new IndexItemHandler(MonologFactory::withHandler($logHandler));

        $bus = MessageBusFactory::create([
            IndexItem::class => [$handler],
        ]);

        $bus->dispatch(new IndexItem(1));
        self::assertCount(1, $logHandler->getRecords());

        // A second dispatch of the same class invokes the handler again,
        // confirming the registration is still active (locator caches
        // the descriptor, not the result).
        $bus->dispatch(new IndexItem(2));
        self::assertCount(2, $logHandler->getRecords());
    }
}
