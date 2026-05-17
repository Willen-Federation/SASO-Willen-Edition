<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Messaging;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Discards every message it receives.
 *
 * Used by HTTP controllers that *want* to enqueue an async job (e.g.
 * {@see \Saso\Domain\Messaging\Message\ProcessItemDraft}) but run inside a
 * web request where dispatching synchronously would block the response on
 * heavy AI work. The real worker process owns its own
 * {@see \Symfony\Component\Messenger\MessageBus} wiring (see
 * {@see ProcessItemDraftDIContainer}) and reads pending jobs out of the
 * `item_draft` table directly.
 *
 * In other words: the controller writes the row, this bus acknowledges the
 * envelope, and the worker — running on its own clock — picks it up. No
 * scheduling guarantees beyond "the row exists".
 */
final class NullMessageBus implements MessageBusInterface
{
    /**
     * @param iterable<StampInterface> $stamps
     */
    public function dispatch(object $message, array $stamps = []): Envelope
    {
        return $message instanceof Envelope ? $message : new Envelope($message, $stamps);
    }
}
