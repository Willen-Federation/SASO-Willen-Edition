<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Ai;

use Saso\Domain\Ai\AiAssistant;
use Saso\Domain\Ai\ChatRequest;
use Saso\Domain\Ai\ChatResponse;
use Saso\Domain\Ai\EmbeddingRequest;
use Saso\Domain\Ai\EmbeddingResponse;
use Saso\Domain\Ai\Exception\AiContentPolicyException;
use Saso\Domain\Ai\Exception\AiProviderNotConfiguredException;
use Saso\Domain\Ai\Exception\AiRateLimitedException;
use Saso\Domain\Ai\Exception\AiUpstreamException;
use Saso\Domain\Ai\ImageDescriptionResponse;
use Saso\Domain\Ai\ImageRequest;
use Saso\Domain\Ai\StructuredExtractionRequest;
use Saso\Domain\Ai\StructuredExtractionResponse;

final class FallbackChainAssistant implements AiAssistant
{
    /** @param list<AiAssistant> $chain */
    public function __construct(private readonly array $chain)
    {
    }

    public function chatComplete(ChatRequest $request): ChatResponse
    {
        return $this->attempt(static fn (AiAssistant $ai) => $ai->chatComplete($request));
    }

    public function extractStructured(StructuredExtractionRequest $request): StructuredExtractionResponse
    {
        return $this->attempt(static fn (AiAssistant $ai) => $ai->extractStructured($request));
    }

    public function embed(EmbeddingRequest $request): EmbeddingResponse
    {
        return $this->attempt(static fn (AiAssistant $ai) => $ai->embed($request));
    }

    public function describeImage(ImageRequest $request): ImageDescriptionResponse
    {
        return $this->attempt(static fn (AiAssistant $ai) => $ai->describeImage($request));
    }

    /**
     * @template T
     *
     * @param callable(AiAssistant): T $operation
     *
     * @return T
     */
    private function attempt(callable $operation): mixed
    {
        if ($this->chain === []) {
            throw AiProviderNotConfiguredException::for('chain', 'no providers configured');
        }

        $last = null;
        foreach ($this->chain as $assistant) {
            try {
                return $operation($assistant);
            } catch (AiContentPolicyException $e) {
                throw $e;
            } catch (AiRateLimitedException | AiUpstreamException $e) {
                $last = $e;
            }
        }

        throw $last;
    }
}
