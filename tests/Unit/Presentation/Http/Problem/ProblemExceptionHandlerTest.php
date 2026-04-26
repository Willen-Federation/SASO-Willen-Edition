<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Http\Problem;

use Monolog\Handler\TestHandler;
use Monolog\Level;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;
use Saso\Infrastructure\Logging\MonologFactory;
use Saso\Presentation\Http\Problem\ProblemExceptionHandler;
use Saso\Presentation\Http\Problem\ProblemRenderer;

/**
 * The handler emits its body via `echo` inside `emit()`. We capture the
 * output through an output buffer, but our assertions target the
 * `ProblemDetails` object that `handle()` returns and the records captured
 * by Monolog's `TestHandler`. The echoed body is incidental.
 */
final class ProblemExceptionHandlerTest extends TestCase
{
    public function testDomainExceptionMapsToItsErrorCode(): void
    {
        $logHandler = new TestHandler();
        $logger     = MonologFactory::withHandler($logHandler);
        $handler    = new ProblemExceptionHandler($logger, new ProblemRenderer());

        $ex = new class (ErrorCode::AuthInvalidCredentials, 'wrong password', ['user_id' => 7]) extends DomainException {};

        ob_start();
        $problem = $handler->handle($ex, '/api/v1/auth/login');
        ob_end_clean();

        self::assertSame('SASO-AUTH-1001', $problem->code);
        self::assertSame(401, $problem->status);
        self::assertSame('wrong password', $problem->detail);
        self::assertSame('/api/v1/auth/login', $problem->instance);
    }

    public function testGenericThrowableMapsToInfraUnhandled(): void
    {
        $logHandler = new TestHandler();
        $logger     = MonologFactory::withHandler($logHandler);
        $handler    = new ProblemExceptionHandler($logger, new ProblemRenderer());

        ob_start();
        $problem = $handler->handle(new RuntimeException('upstream blew up'), '/api/v1/items');
        ob_end_clean();

        self::assertSame('SASO-INFRA-9000', $problem->code);
        self::assertSame(500, $problem->status);
        self::assertStringContainsString('Reference: '.$problem->traceId, $problem->detail);
    }

    public function testDebugModeLeaksOriginalMessageOnGenericThrowables(): void
    {
        $logHandler = new TestHandler();
        $logger     = MonologFactory::withHandler($logHandler);
        $handler    = new ProblemExceptionHandler($logger, new ProblemRenderer(), debug: true);

        ob_start();
        $problem = $handler->handle(new RuntimeException('SQL syntax error near WHERE'), '/api/v1/items');
        ob_end_clean();

        self::assertSame('SQL syntax error near WHERE', $problem->detail);
    }

    public function testHandlerLogsAtErrorLevelWithTraceId(): void
    {
        $logHandler = new TestHandler();
        $logger     = MonologFactory::withHandler($logHandler);
        $handler    = new ProblemExceptionHandler($logger, new ProblemRenderer());

        $ex = new class (ErrorCode::AuthCsrfMismatch, 'csrf token did not validate') extends DomainException {};

        ob_start();
        $problem = $handler->handle($ex, '/api/v1/items');
        ob_end_clean();

        self::assertTrue($logHandler->hasErrorRecords());

        $record = $logHandler->getRecords()[0];
        self::assertSame(Level::Error, $record->level);
        self::assertSame($problem->traceId, $record->context['traceId']);
        self::assertSame('SASO-AUTH-1003', $record->context['code']);
        self::assertSame('csrf token did not validate', $record->message);
        // TraceIdProcessor promotes traceId into extra.
        self::assertSame($problem->traceId, $record->extra['traceId']);
    }

    public function testHandlerHonoursTypeBaseUrl(): void
    {
        $logHandler = new TestHandler();
        $logger     = MonologFactory::withHandler($logHandler);
        $handler    = new ProblemExceptionHandler(
            $logger,
            new ProblemRenderer(),
            typeBaseUrl: 'https://example.test/errors#',
        );

        $ex = new class (ErrorCode::AuthForbidden) extends DomainException {};

        ob_start();
        $problem = $handler->handle($ex, '/api/v1/items');
        ob_end_clean();

        self::assertSame(
            'https://example.test/errors#SASO-AUTH-1005',
            $problem->type,
        );
    }
}
