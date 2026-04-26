<?php

declare(strict_types=1);

namespace Saso\Domain\Ai\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

/**
 * Thrown when an AI provider refuses a request as policy-violating
 * (OpenAI's `content_filter`, Anthropic's safety refusal, Gemini's
 * `BLOCKED_*` reason).
 *
 * The fallback chain does NOT retry on this exception — every major
 * provider applies similar policies and bouncing the request just
 * burns quota.
 */
final class AiContentPolicyException extends DomainException
{
    public static function for(string $providerName, string $reason): self
    {
        return new self(
            ErrorCode::AiContentPolicy,
            sprintf(
                'AI provider "%s" refused the request: %s',
                $providerName,
                $reason,
            ),
            ['provider' => $providerName, 'reason' => $reason],
        );
    }
}
