<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Ai;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Saso\Domain\Ai\Exception\AiContentPolicyException;
use Saso\Domain\Ai\Exception\AiContextExceededException;
use Saso\Domain\Ai\Exception\AiProviderNotConfiguredException;
use Saso\Domain\Ai\Exception\AiRateLimitedException;
use Saso\Domain\Ai\Exception\AiResponseMalformedException;
use Saso\Domain\Ai\Exception\AiUpstreamException;
use Saso\Domain\Shared\ErrorCode;

final class AiExceptionTest extends TestCase
{
    public function testProviderNotConfigured(): void
    {
        $ex = AiProviderNotConfiguredException::for('openai', 'chatComplete');

        self::assertSame(ErrorCode::AiProviderNotConfigured, $ex->errorCode());
        self::assertSame(503, $ex->errorCode()->httpStatus());
        self::assertSame('openai', $ex->context()['provider']);
        self::assertSame('chatComplete', $ex->context()['operation']);
    }

    public function testRateLimited(): void
    {
        $ex = AiRateLimitedException::for('openai', 30);

        self::assertSame(ErrorCode::AiRateLimited, $ex->errorCode());
        self::assertSame(429, $ex->errorCode()->httpStatus());
        self::assertSame(30, $ex->context()['retry_after_seconds']);
    }

    public function testResponseMalformed(): void
    {
        $ex = AiResponseMalformedException::for('gemini', 'invalid JSON: trailing comma');

        self::assertSame(ErrorCode::AiResponseMalformed, $ex->errorCode());
        self::assertSame(422, $ex->errorCode()->httpStatus());
        self::assertStringContainsString('trailing comma', $ex->getMessage());
    }

    public function testContextExceeded(): void
    {
        $ex = AiContextExceededException::for('claude', 220_000, 200_000);

        self::assertSame(ErrorCode::AiContextExceeded, $ex->errorCode());
        self::assertSame(220_000, $ex->context()['token_estimate']);
        self::assertSame(200_000, $ex->context()['context_limit']);
    }

    public function testContentPolicy(): void
    {
        $ex = AiContentPolicyException::for('openai', 'safety');

        self::assertSame(ErrorCode::AiContentPolicy, $ex->errorCode());
        self::assertSame(422, $ex->errorCode()->httpStatus());
    }

    public function testUpstreamCarriesPrevious(): void
    {
        $previous = new RuntimeException('connect timeout');
        $ex       = AiUpstreamException::for('gemini', 'connect timeout', $previous);

        self::assertSame(ErrorCode::InfraUnhandled, $ex->errorCode());
        self::assertSame($previous, $ex->getPrevious());
        self::assertSame('gemini', $ex->context()['provider']);
    }
}
