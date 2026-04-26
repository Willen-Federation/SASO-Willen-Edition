<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Ai;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Ai\ChatMessage;
use Saso\Domain\Ai\ChatRequest;
use Saso\Domain\Ai\EmbeddingRequest;
use Saso\Domain\Ai\Exception\AiProviderNotConfiguredException;
use Saso\Domain\Ai\ImageRequest;
use Saso\Domain\Ai\StructuredExtractionRequest;
use Saso\Domain\Shared\ErrorCode;
use Saso\Infrastructure\Ai\NullAssistant;

final class NullAssistantTest extends TestCase
{
    public function testChatCompleteThrows(): void
    {
        $this->expectException(AiProviderNotConfiguredException::class);

        (new NullAssistant())->chatComplete(
            new ChatRequest(messages: [ChatMessage::user('hi')]),
        );
    }

    public function testExtractStructuredThrows(): void
    {
        $this->expectException(AiProviderNotConfiguredException::class);

        (new NullAssistant())->extractStructured(
            new StructuredExtractionRequest(
                instruction: 'extract',
                sourceText: 'src',
                jsonSchema: ['type' => 'object'],
            ),
        );
    }

    public function testEmbedThrows(): void
    {
        $this->expectException(AiProviderNotConfiguredException::class);

        (new NullAssistant())->embed(new EmbeddingRequest(textInputs: ['x']));
    }

    public function testDescribeImageThrows(): void
    {
        $this->expectException(AiProviderNotConfiguredException::class);

        (new NullAssistant())->describeImage(
            new ImageRequest(imageBytes: 'b', prompt: 'p'),
        );
    }

    public function testExceptionCarriesNullProvider(): void
    {
        try {
            (new NullAssistant())->embed(new EmbeddingRequest(textInputs: ['x']));
            self::fail('expected exception');
        } catch (AiProviderNotConfiguredException $ex) {
            self::assertSame(ErrorCode::AiProviderNotConfigured, $ex->errorCode());
            self::assertSame('null', $ex->context()['provider']);
            self::assertSame('embed', $ex->context()['operation']);
        }
    }
}
