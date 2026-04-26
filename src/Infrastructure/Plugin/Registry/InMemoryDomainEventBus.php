<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Plugin\Registry;

use Saso\Domain\Event\DomainEvent;
use Saso\Domain\Plugin\Registry\DomainEventBus;

/**
 * In-process {@see DomainEventBus}. The only production
 * implementation — buses are request-scoped.
 *
 * Listeners for a given event class run in registration order. A
 * listener that throws short-circuits the chain; the publisher
 * sees the exception and decides what to do (typically: log + bail
 * via the Problem Details handler, or compensate the partial
 * write).
 *
 * Listener selection uses `is_a()` so subclass events fire the
 * superclass listeners too — `publish(SpecificEvent)` triggers
 * listeners subscribed to `BaseEvent`.
 */
final class InMemoryDomainEventBus implements DomainEventBus
{
    /** @var array<class-string<DomainEvent>, list<callable(DomainEvent): void>> */
    private array $listeners = [];

    public function subscribe(string $eventClass, callable $listener): void
    {
        if (!isset($this->listeners[$eventClass])) {
            $this->listeners[$eventClass] = [];
        }
        $this->listeners[$eventClass][] = $listener;
    }

    public function publish(DomainEvent $event): void
    {
        foreach ($this->listeners as $eventClass => $listeners) {
            if (!is_a($event, $eventClass)) {
                continue;
            }
            foreach ($listeners as $listener) {
                $listener($event);
            }
        }
    }

    public function listenerCount(string $eventClass): int
    {
        return count($this->listeners[$eventClass] ?? []);
    }
}
