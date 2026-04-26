<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Ai;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Ai\ChatMessage;
use Saso\Domain\Ai\ChatRequest;

final class ChatRequestTest extends TestCase
{
    public function testStoresFields(): void
    {
        $r = new ChatRequest(
            messages: [ChatMessage::user('hi')],
            temperature: 0.7,
            maxTokens: 256,
        );

        self::assertCount(1, $r->messages);
        self::assertSame(0.7, $r->temperature);
        self::assertSame(256, $r->maxTokens);
        self::assertSame(ChatRequest::FORMAT_TEXT, $r->responseFormat);
    }

    public function testRejectsEmptyMessages(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ChatRequest(messages: []);
    }

    public function testRejectsTemperatureOutOfRange(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ChatRequest(messages: [ChatMessage::user('x')], temperature: 2.5);
    }

    public function testRejectsNonPositiveMaxTokens(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ChatRequest(messages: [ChatMessage::user('x')], maxTokens: 0);
    }

    public function testRejectsUnknownResponseFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ChatRequest(
            messages: [ChatMessage::user('x')],
            responseFormat: 'xml',
        );
    }

    public function testJsonSchemaFormatRequiresSchema(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('jsonSchema is required');

        new ChatRequest(
            messages: [ChatMessage::user('x')],
            responseFormat: ChatRequest::FORMAT_JSON_SCHEMA,
        );
    }

    public function testJsonObjectFormatDoesNotRequireSchema(): void
    {
        $r = new ChatRequest(
            messages: [ChatMessage::user('x')],
            responseFormat: ChatRequest::FORMAT_JSON_OBJECT,
        );

        self::assertSame(ChatRequest::FORMAT_JSON_OBJECT, $r->responseFormat);
        self::assertNull($r->jsonSchema);
    }
}
