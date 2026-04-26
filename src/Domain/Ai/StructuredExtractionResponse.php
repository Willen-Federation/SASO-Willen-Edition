<?php

declare(strict_types=1);

namespace Saso\Domain\Ai;

/**
 * Result of {@see AiAssistant::extractStructured()}.
 *
 * `data` carries the parsed payload conforming to the request's
 * JSON schema. Adapters that get back malformed JSON throw
 * {@see Exception\AiResponseMalformedException} rather than returning
 * a half-typed array.
 */
final readonly class StructuredExtractionResponse
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public array $data,
        public AiUsage $usage,
        public string $model,
    ) {
    }
}
