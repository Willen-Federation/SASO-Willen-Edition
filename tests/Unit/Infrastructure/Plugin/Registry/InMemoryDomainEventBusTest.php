<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Plugin\Registry;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Saso\Domain\Event\DomainEvent;
use Saso\Infrastructure\Plugin\Registry\InMemoryDomainEventBus;

final class InMemoryDomainEventBusTest extends TestCase
{
    public function testListenerCountIsZeroWhenEmpty(): void
    {
        $bus = new InMemoryDomainEventBus();

        self::assertSame(0, $bus->listenerCount(SampleEvent::class));
    }

    public function testSubscribedListenerReceivesPublishedEvent(): void
    {
        $bus      = new InMemoryDomainEventBus();
        $received = [];

        $bus->subscribe(SampleEvent::class, function (SampleEvent $e) use (&$received): void {
            $received[] = $e->payload;
        });

        $bus->publish(new SampleEvent('hello'));

        self::assertSame(['hello'], $received);
    }

    public function testListenersRunInRegistrationOrder(): void
    {
        $bus     = new InMemoryDomainEventBus();
        $order   = [];

        $bus->subscribe(SampleEvent::class, static function () use (&$order): void {
            $order[] = 'first';
        });
        $bus->subscribe(SampleEvent::class, static function () use (&$order): void {
            $order[] = 'second';
        });

        $bus->publish(new SampleEvent('x'));

        self::assertSame(['first', 'second'], $order);
    }

    public function testSubclassEventTriggersSuperclassSubscribers(): void
    {
        $bus      = new InMemoryDomainEventBus();
        $received = [];

        $bus->subscribe(SampleEvent::class, static function (SampleEvent $e) use (&$received): void {
            $received[] = $e->payload;
        });

        $bus->publish(new ChildEvent('child'));

        self::assertSame(['child'], $received);
    }

    public function testListenerExceptionStopsTheChain(): void
    {
        $bus     = new InMemoryDomainEventBus();
        $reached = false;

        $bus->subscribe(SampleEvent::class, static function () {
            throw new RuntimeException('listener bug');
        });
        $bus->subscribe(SampleEvent::class, static function () use (&$reached): void {
            $reached = true;
        });

        $this->expectException(RuntimeException::class);

        try {
            $bus->publish(new SampleEvent('x'));
        } finally {
            self::assertFalse($reached, 'subsequent listener must not run after a throw');
        }
    }

    public function testListenerCountReturnsExactNumber(): void
    {
        $bus = new InMemoryDomainEventBus();
        $bus->subscribe(SampleEvent::class, static fn () => null);
        $bus->subscribe(SampleEvent::class, static fn () => null);
        $bus->subscribe(SampleEvent::class, static fn () => null);

        self::assertSame(3, $bus->listenerCount(SampleEvent::class));
    }
}

class SampleEvent implements DomainEvent
{
    public function __construct(public string $payload)
    {
    }
}

final class ChildEvent extends SampleEvent
{
}
