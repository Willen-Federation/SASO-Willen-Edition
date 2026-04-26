<?php

declare(strict_types=1);

namespace Saso\Domain\Ai;

/**
 * Result of {@see AiAssistant::describeImage()}.
 *
 * Same shape as {@see ChatResponse} — image-description is a chat
 * completion with a single image input — but kept separate so call
 * sites that mean "describe an image" don't have to construct a
 * `ChatRequest` with implicit semantics.
 */
final readonly class ImageDescriptionResponse
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
