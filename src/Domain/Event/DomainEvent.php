<?php

declare(strict_types=1);

namespace Saso\Domain\Event;

/**
 * Marker interface for domain events that fan out through
 * {@see \Saso\Domain\Plugin\Registry\DomainEventBus}
 * (cf. ADR 0015).
 *
 * Plugins subscribe to specific event classes; the bus dispatches
 * synchronously in the publishing request (no queue — for async
 * fan-out, the publisher emits a Symfony Messenger message in
 * addition).
 */
interface DomainEvent
{
}
