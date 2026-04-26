<?php

declare(strict_types=1);

namespace Saso\Domain\Plugin\Registry;

use Saso\Domain\Event\DomainEvent;

/**
 * Plugin extension point for hooking domain events
 * (cf. ADR 0015).
 *
 * The bus is synchronous and unordered — listeners run in
 * registration order within the publishing request. Plugins that
 * need queued fan-out should publish a Symfony Messenger message
 * from their listener (cf. ADR 0013); the bus is the place to
 * register the listener that does so.
 *
 * A listener that throws stops the dispatch chain — subsequent
 * listeners do NOT run. This is by design: a buggy plugin should
 * surface its failure at the publisher rather than silently
 * swallowing it. Operators who want isolation between listeners
 * wrap each in a `try/catch` at registration time.
 */
interface DomainEventBus
{
    /**
     * @template T of DomainEvent
     *
     * @param class-string<T> $eventClass
     * @param callable(T): void $listener
     */
    public function subscribe(string $eventClass, callable $listener): void;

    public function publish(DomainEvent $event): void;

    /**
     * Returns the count of listeners registered for the given event
     * class. Useful for tests; production code should not rely on
     * this number.
     *
     * @param class-string<DomainEvent> $eventClass
     */
    public function listenerCount(string $eventClass): int;
}
