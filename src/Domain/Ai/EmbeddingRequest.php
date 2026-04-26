<?php

declare(strict_types=1);

namespace Saso\Domain\Ai;

use InvalidArgumentException;

/**
 * Request to {@see AiAssistant::embed()}. Supports both text and
 * image inputs (image bytes for vision-embedding models).
 */
final readonly class EmbeddingRequest
{
    /**
     * @param list<string> $textInputs each input becomes one vector
     * @param list<string> $imageInputs raw bytes; each becomes one vector
     */
    public function __construct(
        public array $textInputs = [],
        public array $imageInputs = [],
        public EmbeddingTask $task = EmbeddingTask::Similarity,
        public ?int $dimensions = null,
    ) {
        if ($textInputs === [] && $imageInputs === []) {
            throw new InvalidArgumentException(
                'EmbeddingRequest must carry at least one text or image input.',
            );
        }
        if ($dimensions !== null && $dimensions < 16) {
            throw new InvalidArgumentException(
                'EmbeddingRequest.dimensions must be ≥ 16 when set.',
            );
        }
    }
}
