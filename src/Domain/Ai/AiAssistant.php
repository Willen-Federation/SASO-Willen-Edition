<?php

declare(strict_types=1);

namespace Saso\Domain\Ai;

use Saso\Domain\Ai\Exception\AiContentPolicyException;
use Saso\Domain\Ai\Exception\AiContextExceededException;
use Saso\Domain\Ai\Exception\AiProviderNotConfiguredException;
use Saso\Domain\Ai\Exception\AiRateLimitedException;
use Saso\Domain\Ai\Exception\AiResponseMalformedException;
use Saso\Domain\Ai\Exception\AiUpstreamException;

/**
 * Vendor-agnostic AI gateway (cf. ADR 0009).
 *
 * Concrete implementations: `OpenAiAssistant`, `GeminiAssistant`,
 * `ClaudeAssistant` (M6-F), and `NullAssistant` (this PR — used for
 * tests and for environments without an AI key).
 *
 * Implementations MUST:
 *
 *   * throw {@see AiProviderNotConfiguredException} when no API key is
 *     present for the requested operation;
 *   * throw {@see AiRateLimitedException} on HTTP 429 / equivalent;
 *   * throw {@see AiResponseMalformedException} when the provider
 *     returns non-parseable JSON for a structured request;
 *   * throw {@see AiContextExceededException} when the prompt + max
 *     tokens exceed the provider's context window;
 *   * throw {@see AiContentPolicyException} when the provider refuses
 *     a request as policy-violating;
 *   * throw {@see AiUpstreamException} for any other transient
 *     failure (5xx, timeout, network) so the
 *     {@see \Saso\Infrastructure\Ai\FallbackChainAssistant} (M6-F)
 *     can retry against a sibling provider.
 *
 * The `NullAssistant` shipped in this PR throws
 * {@see AiProviderNotConfiguredException} from every method — tests
 * that need a deterministic positive response inject a fake.
 */
interface AiAssistant
{
    /**
     * @throws AiProviderNotConfiguredException
     * @throws AiRateLimitedException
     * @throws AiContextExceededException
     * @throws AiContentPolicyException
     * @throws AiUpstreamException
     */
    public function chatComplete(ChatRequest $request): ChatResponse;

    /**
     * @throws AiProviderNotConfiguredException
     * @throws AiRateLimitedException
     * @throws AiResponseMalformedException
     * @throws AiContextExceededException
     * @throws AiContentPolicyException
     * @throws AiUpstreamException
     */
    public function extractStructured(StructuredExtractionRequest $request): StructuredExtractionResponse;

    /**
     * @throws AiProviderNotConfiguredException
     * @throws AiRateLimitedException
     * @throws AiContextExceededException
     * @throws AiUpstreamException
     */
    public function embed(EmbeddingRequest $request): EmbeddingResponse;

    /**
     * @throws AiProviderNotConfiguredException
     * @throws AiRateLimitedException
     * @throws AiContextExceededException
     * @throws AiContentPolicyException
     * @throws AiUpstreamException
     */
    public function describeImage(ImageRequest $request): ImageDescriptionResponse;
}
