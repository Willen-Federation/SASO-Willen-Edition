<?php

declare(strict_types=1);

namespace Saso\Domain\Ai;

use InvalidArgumentException;

/**
 * Request to {@see AiAssistant::extractStructured()}.
 *
 * Distinct from {@see ChatRequest} because the call site says "give me
 * structured fields" rather than "have a conversation". Adapters
 * default to JSON-schema output where the provider supports it
 * (OpenAI Structured Outputs, Gemini response schema, Anthropic tool
 * use); fall back to JSON object output otherwise. Used by the M6-G
 * item registration pipeline (barcode → AI extraction →
 * `ItemAttributeValue` rows).
 *
 * @see \Saso\Domain\Ai\ChatRequest::FORMAT_JSON_SCHEMA
 */
final readonly class StructuredExtractionRequest
{
    /**
     * @param array<string, mixed> $jsonSchema required output shape
     */
    public function __construct(
        public string $instruction,
        public string $sourceText,
        public array $jsonSchema,
        public ?string $imageBytes = null,
        public ?string $imageMimeType = null,
        public int $maxTokens = 2048,
    ) {
        if ($instruction === '') {
            throw new InvalidArgumentException('StructuredExtractionRequest.instruction must not be empty.');
        }
        if ($sourceText === '' && $imageBytes === null) {
            throw new InvalidArgumentException(
                'StructuredExtractionRequest must carry at least sourceText or imageBytes.',
            );
        }
        if ($jsonSchema === []) {
            throw new InvalidArgumentException(
                'StructuredExtractionRequest.jsonSchema must not be empty.',
            );
        }
        if ($maxTokens < 1) {
            throw new InvalidArgumentException(
                'StructuredExtractionRequest.maxTokens must be ≥ 1.',
            );
        }
    }
}
