<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Ai;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Ai\AiAssistant;
use Saso\Domain\Ai\AiUsage;
use Saso\Domain\Ai\ChatMessage;
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
use Saso\Infrastructure\Ai\FallbackChainAssistant;

final class FallbackChainAssistantTest extends TestCase
{
    public function testEmptyChainThrowsProviderNotConfigured(): void
    {
        $this->expectException(AiProviderNotConfiguredException::class);

        (new FallbackChainAssistant([]))->chatComplete(
            new ChatRequest(messages: [ChatMessage::user('hi')]),
        );
    }

    public function testFirstSuccessfulProviderIsReturned(): void
    {
        $expected = new ChatResponse('hello', new AiUsage(), 'fake-model');
        $succeeds = $this->makeAssistantThatReturnsChat($expected);
        $chain    = new FallbackChainAssistant([$succeeds]);

        $result = $chain->chatComplete(new ChatRequest(messages: [ChatMessage::user('hi')]));

        self::assertSame($expected, $result);
    }

    public function testFallsToSecondProviderOnRateLimit(): void
    {
        $rateLimited = $this->makeAssistantThatThrows(AiRateLimitedException::for('first'));
        $expected    = new ChatResponse('ok', new AiUsage(), 'second-model');
        $succeeds    = $this->makeAssistantThatReturnsChat($expected);

        $chain  = new FallbackChainAssistant([$rateLimited, $succeeds]);
        $result = $chain->chatComplete(new ChatRequest(messages: [ChatMessage::user('hi')]));

        self::assertSame($expected, $result);
    }

    public function testFallsToSecondProviderOnUpstreamError(): void
    {
        $fails    = $this->makeAssistantThatThrows(AiUpstreamException::for('first', 'timeout'));
        $expected = new ChatResponse('ok', new AiUsage(), 'second-model');
        $succeeds = $this->makeAssistantThatReturnsChat($expected);

        $chain  = new FallbackChainAssistant([$fails, $succeeds]);
        $result = $chain->chatComplete(new ChatRequest(messages: [ChatMessage::user('hi')]));

        self::assertSame($expected, $result);
    }

    public function testContentPolicyExceptionPropagatesImmediately(): void
    {
        $policyViolation = $this->makeAssistantThatThrows(
            AiContentPolicyException::for('first', 'policy'),
        );
        $shouldNotBeUsed = $this->makeAssistantThatReturnsChat(
            new ChatResponse('never', new AiUsage(), 'second-model'),
        );

        $chain = new FallbackChainAssistant([$policyViolation, $shouldNotBeUsed]);

        $this->expectException(AiContentPolicyException::class);

        $chain->chatComplete(new ChatRequest(messages: [ChatMessage::user('bad content')]));
    }

    public function testAllFailuresRethrowsLastException(): void
    {
        $first  = $this->makeAssistantThatThrows(AiRateLimitedException::for('first'));
        $second = $this->makeAssistantThatThrows(AiUpstreamException::for('second', 'timeout'));

        $chain = new FallbackChainAssistant([$first, $second]);

        $this->expectException(AiUpstreamException::class);

        $chain->chatComplete(new ChatRequest(messages: [ChatMessage::user('hi')]));
    }

    private function makeAssistantThatReturnsChat(ChatResponse $response): AiAssistant
    {
        return new class ($response) implements AiAssistant {
            public function __construct(private readonly ChatResponse $r)
            {
            }

            public function chatComplete(ChatRequest $request): ChatResponse
            {
                return $this->r;
            }

            public function extractStructured(StructuredExtractionRequest $request): StructuredExtractionResponse
            {
                throw new \LogicException('not implemented in stub');
            }

            public function embed(EmbeddingRequest $request): EmbeddingResponse
            {
                throw new \LogicException('not implemented in stub');
            }

            public function describeImage(ImageRequest $request): ImageDescriptionResponse
            {
                throw new \LogicException('not implemented in stub');
            }
        };
    }

    private function makeAssistantThatThrows(\Throwable $e): AiAssistant
    {
        return new class ($e) implements AiAssistant {
            public function __construct(private readonly \Throwable $e)
            {
            }

            public function chatComplete(ChatRequest $request): ChatResponse
            {
                throw $this->e;
            }

            public function extractStructured(StructuredExtractionRequest $request): StructuredExtractionResponse
            {
                throw $this->e;
            }

            public function embed(EmbeddingRequest $request): EmbeddingResponse
            {
                throw $this->e;
            }

            public function describeImage(ImageRequest $request): ImageDescriptionResponse
            {
                throw $this->e;
            }
        };
    }
}
