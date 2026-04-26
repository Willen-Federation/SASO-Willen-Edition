<?php

declare(strict_types=1);

namespace Saso\Domain\Ai;

use InvalidArgumentException;

/**
 * Request to {@see AiAssistant::chatComplete()}.
 *
 * The shape is intentionally narrow — temperature and max-tokens, plus
 * a `responseFormat` hint that selects between free text, JSON object,
 * and JSON-schema-constrained output. Streaming, tool calls, and
 * function calls are out of scope; M6 features that need them get
 * dedicated `extractStructured()` semantics instead.
 */
final readonly class ChatRequest
{
    public const FORMAT_TEXT        = 'text';
    public const FORMAT_JSON_OBJECT = 'json_object';
    public const FORMAT_JSON_SCHEMA = 'json_schema';

    /**
     * @param list<ChatMessage> $messages conversation, oldest first
     * @param array<string, mixed>|null $jsonSchema present iff $responseFormat = json_schema
     */
    public function __construct(
        public array $messages,
        public float $temperature = 0.0,
        public int $maxTokens = 1024,
        public string $responseFormat = self::FORMAT_TEXT,
        public ?array $jsonSchema = null,
    ) {
        if ($messages === []) {
            throw new InvalidArgumentException('ChatRequest.messages must not be empty.');
        }
        if ($temperature < 0.0 || $temperature > 2.0) {
            throw new InvalidArgumentException('ChatRequest.temperature must be in [0, 2].');
        }
        if ($maxTokens < 1) {
            throw new InvalidArgumentException('ChatRequest.maxTokens must be ≥ 1.');
        }
        if (!in_array(
            $responseFormat,
            [self::FORMAT_TEXT, self::FORMAT_JSON_OBJECT, self::FORMAT_JSON_SCHEMA],
            true,
        )) {
            throw new InvalidArgumentException(sprintf(
                'ChatRequest.responseFormat must be text|json_object|json_schema (got %s).',
                $responseFormat,
            ));
        }
        if ($responseFormat === self::FORMAT_JSON_SCHEMA && $jsonSchema === null) {
            throw new InvalidArgumentException(
                'ChatRequest.jsonSchema is required when responseFormat = json_schema.',
            );
        }
    }
}
