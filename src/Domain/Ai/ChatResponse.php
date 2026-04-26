<?php

declare(strict_types=1);

namespace Saso\Domain\Ai;

/**
 * Response from {@see AiAssistant::chatComplete()}.
 *
 * `content` is always populated; for `json_object` / `json_schema`
 * requests it carries the raw JSON text the provider returned —
 * callers that want a parsed payload call `decoded()`.
 *
 * `usage` is best-effort: providers that report token counts populate
 * it, those that don't leave the fields zero. The cost-estimator (M6-D)
 * uses these to feed the AI rate limiter.
 */
final readonly class ChatResponse
{
    public function __construct(
        public string $content,
        public AiUsage $usage,
        public string $model,
        public ?string $finishReason = null,
    ) {
    }

    /**
     * @return array<string, mixed>|list<mixed>|null
     */
    public function decoded(): array|null
    {
        $decoded = json_decode($this->content, associative: true);

        return is_array($decoded) ? $decoded : null;
    }
}
