<?php

declare(strict_types=1);

namespace Saso\Application\Messaging\Handler;

use Psr\Log\LoggerInterface;
use Saso\Domain\Messaging\Message\IndexItem;

/**
 * Handler for {@see IndexItem} messages.
 *
 * In M6-C this is a placeholder that records the dispatch through
 * Monolog — the real OpenSearch upsert lands in M6-D once
 * `Saso\Domain\Search\SearchIndex` exists. The shape of the handler
 * (ctor-injected dependencies, single `__invoke`, no return value)
 * is final; M6-D swaps the `LoggerInterface` for the
 * `SearchIndex` + logger pair.
 *
 * Symfony Messenger discovers this handler via the message-class
 * type-hint on `__invoke()` — no annotations needed.
 */
final class IndexItemHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(IndexItem $message): void
    {
        $this->logger->info('IndexItem received', [
            'item_id' => $message->itemId,
            'reason'  => $message->reason,
        ]);
    }
}
