<?php

declare(strict_types=1);

namespace Saso\Presentation\Http\Problem;

use PDOException;
use Psr\Log\LoggerInterface;
use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;
use Saso\Infrastructure\Translation\Translator;
use Throwable;

/**
 * Single termination point for every uncaught throwable.
 *
 * Behaviour matrix:
 *
 *   - `DomainException`  → response carries the subclass's `ErrorCode` and
 *                          a translated `title`/`detail` resolved against
 *                          the request locale, falling back to the
 *                          exception's English message and the code's
 *                          {@see ErrorCode::defaultTitle()}.
 *   - `PDOException`     → response carries `SASO-INFRA-9001` (503), so
 *                          operators can immediately distinguish a database
 *                          outage / schema drift from generic application
 *                          bugs. SQLSTATE is captured in the log context.
 *   - any other throwable → response carries `SASO-INFRA-9000` and either
 *                          a generic message (production) or the original
 *                          message (debug mode). The full stack is logged.
 *
 * In every case the response body carries a freshly-generated `traceId`;
 * the same id appears in the log entry under the `traceId` key, which is
 * what operators use to correlate a support report to a server-side trace.
 *
 * The translator is optional. When it is null the handler falls back to
 * the English defaults baked into {@see ErrorCode::defaultTitle()} — this
 * keeps the handler usable from setup paths (the installer, early
 * bootstrap) where the i18n catalogue may not yet be wired.
 */
final class ProblemExceptionHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ProblemRenderer $renderer,
        private readonly bool $debug = false,
        private readonly ?string $typeBaseUrl = null,
        private readonly ?Translator $translator = null,
    ) {
    }

    public function handle(Throwable $exception, string $instance, ?string $locale = null): ProblemDetails
    {
        $traceId = TraceId::generate();

        if ($exception instanceof DomainException) {
            $code           = $exception->errorCode();
            $detailFallback = $exception->getMessage();
            $logCtx         = $exception->context();
        } elseif ($exception instanceof PDOException) {
            $code           = ErrorCode::InfraDatabaseUnavailable;
            $detailFallback = $this->debug
                ? $exception->getMessage()
                : 'The database is unavailable or the schema is out of date. Reference: '.$traceId;
            $logCtx = [
                'sqlstate' => $exception->getCode() !== 0 ? (string) $exception->getCode() : null,
                'errorInfo' => $exception->errorInfo ?? null,
            ];
        } else {
            $code           = ErrorCode::InfraUnhandled;
            $detailFallback = $this->debug
                ? $exception->getMessage()
                : 'An unexpected error occurred. Reference: '.$traceId;
            $logCtx = [];
        }

        $title  = $this->resolveTitle($code, $locale);
        $detail = $this->resolveDetail($code, $detailFallback, $traceId, $locale);

        $this->logger->error($exception->getMessage(), [
            'traceId'   => $traceId,
            'code'      => $code->value,
            'exception' => $exception::class,
            'file'      => $exception->getFile(),
            'line'      => $exception->getLine(),
            'context'   => $logCtx,
            'trace'     => $exception->getTraceAsString(),
        ]);

        $problem = ProblemDetails::fromError(
            code: $code,
            title: $title,
            detail: $detail,
            instance: $instance,
            traceId: $traceId,
            typeBaseUrl: $this->typeBaseUrl,
        );

        $this->renderer->emit($problem);

        return $problem;
    }

    private function resolveTitle(ErrorCode $code, ?string $locale): string
    {
        $fallback = $code->defaultTitle();

        if ($this->translator === null) {
            return $fallback;
        }

        return $this->translator->trans(
            key: $code->translationKey().'.title',
            locale: $locale,
            fallback: $fallback,
        );
    }

    private function resolveDetail(ErrorCode $code, string $fallback, string $traceId, ?string $locale): string
    {
        if ($this->translator === null) {
            return $fallback;
        }

        return $this->translator->trans(
            key: $code->translationKey().'.detail',
            params: ['{traceId}' => $traceId],
            locale: $locale,
            fallback: $fallback,
        );
    }
}
