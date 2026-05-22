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
use Saso\Infrastructure\Translation\TranslatorFactory;
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

    public function testPdoExceptionMapsToInfraDatabaseUnavailable(): void
    {
        $logHandler = new TestHandler();
        $logger     = MonologFactory::withHandler($logHandler);
        $handler    = new ProblemExceptionHandler($logger, new ProblemRenderer());

        $pdoException = new \PDOException(
            'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'i.note\' in \'field list\'',
        );

        ob_start();
        $problem = $handler->handle($pdoException, '/api/v1/items');
        ob_end_clean();

        // 9001 (Database unavailable, 503) — operators can immediately
        // distinguish a schema-drift / DB-down condition from a generic
        // application bug (which would still be 9000 / 500).
        self::assertSame('SASO-INFRA-9001', $problem->code);
        self::assertSame(503, $problem->status);
        self::assertStringContainsString('Reference: '.$problem->traceId, $problem->detail);
    }

    public function testPdoExceptionLogsSqlstate(): void
    {
        $logHandler = new TestHandler();
        $logger     = MonologFactory::withHandler($logHandler);
        $handler    = new ProblemExceptionHandler($logger, new ProblemRenderer());

        $pdoException = new \PDOException('Unknown column');
        // PDOException's `code` is normally the SQLSTATE; PDOException's
        // ctor stores the message but `getCode()` returns 0 unless set,
        // so we set it explicitly to mimic real PDO behaviour.
        $reflection = new \ReflectionProperty(\Exception::class, 'code');
        $reflection->setValue($pdoException, '42S22');

        ob_start();
        $handler->handle($pdoException, '/api/v1/items');
        ob_end_clean();

        $records = $logHandler->getRecords();
        self::assertNotEmpty($records);
        self::assertSame('42S22', $records[0]->context['context']['sqlstate']);
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

    public function testTranslatorResolvesLocalisedTitleAndDetail(): void
    {
        $logHandler = new TestHandler();
        $logger     = MonologFactory::withHandler($logHandler);
        $translator = TranslatorFactory::create();
        $handler    = new ProblemExceptionHandler(
            $logger,
            new ProblemRenderer(),
            translator: $translator,
        );

        $ex = new class (ErrorCode::AuthInvalidCredentials, 'wrong password') extends DomainException {};

        ob_start();
        $problem = $handler->handle($ex, '/api/v1/auth/login', locale: 'ja');
        ob_end_clean();

        self::assertSame('認証情報が正しくありません', $problem->title);
        self::assertSame(
            '入力された認証情報は有効なアカウントと一致しません。',
            $problem->detail,
        );
    }

    public function testTranslatorFallsBackToExceptionMessageForUntranslatedDetails(): void
    {
        $logHandler = new TestHandler();
        $logger     = MonologFactory::withHandler($logHandler);
        $translator = TranslatorFactory::create();
        $handler    = new ProblemExceptionHandler(
            $logger,
            new ProblemRenderer(),
            translator: $translator,
        );

        $ex = new class (ErrorCode::AuthInvalidCredentials, 'request-specific reason') extends DomainException {};

        ob_start();
        $problem = $handler->handle($ex, '/api/v1/auth/login', locale: 'en');
        ob_end_clean();

        // English detail string from translations/en.yaml — not the
        // exception message, since the catalogue has an entry.
        self::assertSame(
            'The submitted credentials did not match an active account.',
            $problem->detail,
        );
    }

    public function testTraceIdIsInterpolatedIntoInfraDetail(): void
    {
        $logHandler = new TestHandler();
        $logger     = MonologFactory::withHandler($logHandler);
        $translator = TranslatorFactory::create();
        $handler    = new ProblemExceptionHandler(
            $logger,
            new ProblemRenderer(),
            translator: $translator,
        );

        ob_start();
        $problem = $handler->handle(new RuntimeException('boom'), '/api/v1/items', locale: 'en');
        ob_end_clean();

        self::assertStringContainsString($problem->traceId, $problem->detail);
        self::assertStringNotContainsString('{traceId}', $problem->detail);
    }

    public function testWithoutTranslatorFallsBackToDefaultTitle(): void
    {
        $logHandler = new TestHandler();
        $logger     = MonologFactory::withHandler($logHandler);
        $handler    = new ProblemExceptionHandler($logger, new ProblemRenderer());

        $ex = new class (ErrorCode::AuthForbidden) extends DomainException {};

        ob_start();
        $problem = $handler->handle($ex, '/api/v1/items');
        ob_end_clean();

        self::assertSame('Access denied', $problem->title);
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
