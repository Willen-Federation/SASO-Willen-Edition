<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Ai;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Ai\AiUsage;
use Saso\Domain\Ai\ChatResponse;

final class ChatResponseTest extends TestCase
{
    public function testStoresFields(): void
    {
        $r = new ChatResponse(
            content: 'hello',
            usage: new AiUsage(promptTokens: 10, completionTokens: 5),
            model: 'gpt-test',
            finishReason: 'stop',
        );

        self::assertSame('hello', $r->content);
        self::assertSame(15, $r->usage->totalTokens());
        self::assertSame('gpt-test', $r->model);
        self::assertSame('stop', $r->finishReason);
    }

    public function testDecodedReturnsArrayForValidJson(): void
    {
        $r = new ChatResponse(
            content: '{"title":"book","price":1500}',
            usage: new AiUsage(),
            model: 'gpt-test',
        );

        self::assertSame(['title' => 'book', 'price' => 1500], $r->decoded());
    }

    public function testDecodedReturnsNullForInvalidJson(): void
    {
        $r = new ChatResponse(
            content: 'not json',
            usage: new AiUsage(),
            model: 'gpt-test',
        );

        self::assertNull($r->decoded());
    }
}
