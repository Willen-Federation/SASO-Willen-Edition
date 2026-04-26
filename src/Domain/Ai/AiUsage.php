<?php

declare(strict_types=1);

namespace Saso\Domain\Ai;

/**
 * Token usage report attached to {@see ChatResponse} /
 * {@see EmbeddingResponse} / {@see ImageDescriptionResponse}.
 *
 * Providers that don't report a particular field leave it zero. The
 * cost estimator (M6-D) treats zeros as "unknown" — never as "free".
 */
final readonly class AiUsage
{
    public function __construct(
        public int $promptTokens = 0,
        public int $completionTokens = 0,
        public int $embeddingTokens = 0,
    ) {
    }

    public function totalTokens(): int
    {
        return $this->promptTokens + $this->completionTokens + $this->embeddingTokens;
    }
}
