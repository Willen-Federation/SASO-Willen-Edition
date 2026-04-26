<?php

declare(strict_types=1);

namespace Saso\Domain\Messaging\Message;

/**
 * Marker interface for SASO domain messages dispatched through the
 * Symfony Messenger bus (cf. ADR 0013).
 *
 * Concrete messages are immutable readonly value objects that carry
 * exactly the data their handler needs — no service references, no
 * shared mutable state. Serialisable via PHP's standard mechanism so
 * the message survives a round-trip through the configured transport
 * (Redis Streams in production, Doctrine table on shared hosting,
 * Sync in tests).
 */
interface Message
{
}
