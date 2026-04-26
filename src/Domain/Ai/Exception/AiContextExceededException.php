<?php

declare(strict_types=1);

namespace Saso\Domain\Ai\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

/**
 * Thrown when the prompt + max-tokens sum exceeds the provider's
 * context window. Adapters compute this client-side when possible
 * (token-counting libraries) so the call never leaves the host;
 * otherwise they translate the provider's HTTP error into this
 * exception.
 */
final class AiContextExceededException extends DomainException
{
    public static function for(string $providerName, int $tokenEstimate, int $contextLimit): self
    {
        return new self(
            ErrorCode::AiContextExceeded,
            sprintf(
                'AI provider "%s" rejected the request: estimated %d tokens exceeds context window of %d.',
                $providerName,
                $tokenEstimate,
                $contextLimit,
            ),
            [
                'provider'      => $providerName,
                'token_estimate' => $tokenEstimate,
                'context_limit'  => $contextLimit,
            ],
        );
    }
}
