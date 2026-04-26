<?php

declare(strict_types=1);

namespace Saso\Domain\Ai;

use InvalidArgumentException;

/**
 * Request to {@see AiAssistant::describeImage()}.
 *
 * `imageBytes` is the raw image binary — adapters base64-encode for
 * providers that require it. `prompt` is the instruction
 * ("Describe this product. Return JSON with title/category/specs.").
 */
final readonly class ImageRequest
{
    public function __construct(
        public string $imageBytes,
        public string $prompt,
        public string $mimeType = 'image/jpeg',
        public int $maxTokens = 1024,
        public string $responseFormat = ChatRequest::FORMAT_TEXT,
    ) {
        if ($imageBytes === '') {
            throw new InvalidArgumentException('ImageRequest.imageBytes must not be empty.');
        }
        if ($prompt === '') {
            throw new InvalidArgumentException('ImageRequest.prompt must not be empty.');
        }
        if ($maxTokens < 1) {
            throw new InvalidArgumentException('ImageRequest.maxTokens must be ≥ 1.');
        }
    }
}
