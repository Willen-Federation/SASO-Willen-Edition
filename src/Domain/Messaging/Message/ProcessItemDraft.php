<?php

declare(strict_types=1);

namespace Saso\Domain\Messaging\Message;

use InvalidArgumentException;

/**
 * Tells the worker to enrich an `item_draft` row with ISBN/JAN lookups
 * and AI vision analysis (cf. ADR 0013, plan section A).
 */
final readonly class ProcessItemDraft implements Message
{
    public function __construct(public int $draftId)
    {
        if ($draftId < 1) {
            throw new InvalidArgumentException('ProcessItemDraft.draftId must be a positive integer.');
        }
    }
}
