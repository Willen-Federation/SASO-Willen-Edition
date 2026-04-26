<?php

declare(strict_types=1);

namespace Saso\Domain\Ai\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

/**
 * Thrown when an AI provider returns content that fails to parse
 * against the expected JSON schema for a structured-extraction call.
 *
 * Adapters log the raw response at `debug` level (Monolog) so an
 * operator can inspect it via `make logs`, but the wire response
 * carries only the `traceId` per ADR 0004.
 */
final class AiResponseMalformedException extends DomainException
{
    public static function for(string $providerName, string $reason): self
    {
        return new self(
            ErrorCode::AiResponseMalformed,
            sprintf('AI provider "%s" returned a malformed response: %s', $providerName, $reason),
            ['provider' => $providerName, 'reason' => $reason],
        );
    }
}
