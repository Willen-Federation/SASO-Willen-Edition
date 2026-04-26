<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Ai;

use Saso\Domain\Ai\AiAssistant;
use Saso\Domain\Ai\ChatRequest;
use Saso\Domain\Ai\ChatResponse;
use Saso\Domain\Ai\EmbeddingRequest;
use Saso\Domain\Ai\EmbeddingResponse;
use Saso\Domain\Ai\Exception\AiProviderNotConfiguredException;
use Saso\Domain\Ai\ImageDescriptionResponse;
use Saso\Domain\Ai\ImageRequest;
use Saso\Domain\Ai\StructuredExtractionRequest;
use Saso\Domain\Ai\StructuredExtractionResponse;

/**
 * The default {@see AiAssistant} when no API key is configured or
 * `SAFE_MODE=true`.
 *
 * Every method throws {@see AiProviderNotConfiguredException}. Tests
 * that need a deterministic positive response inject a separate
 * anonymous class implementing {@see AiAssistant} directly — this
 * class is the *gate*, not a stub for happy-path scenarios.
 *
 * Per ADR 0009, the M6-F `Bootstrap` composition root selects this
 * implementation when:
 *   * the request-scope provider key resolves to `null`, OR
 *   * `SAFE_MODE` is `true` in `.env`.
 */
final class NullAssistant implements AiAssistant
{
    public const PROVIDER_NAME = 'null';

    public function chatComplete(ChatRequest $request): ChatResponse
    {
        throw AiProviderNotConfiguredException::for(self::PROVIDER_NAME, 'chatComplete');
    }

    public function extractStructured(StructuredExtractionRequest $request): StructuredExtractionResponse
    {
        throw AiProviderNotConfiguredException::for(self::PROVIDER_NAME, 'extractStructured');
    }

    public function embed(EmbeddingRequest $request): EmbeddingResponse
    {
        throw AiProviderNotConfiguredException::for(self::PROVIDER_NAME, 'embed');
    }

    public function describeImage(ImageRequest $request): ImageDescriptionResponse
    {
        throw AiProviderNotConfiguredException::for(self::PROVIDER_NAME, 'describeImage');
    }
}
