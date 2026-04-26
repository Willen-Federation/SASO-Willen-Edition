<?php

declare(strict_types=1);

namespace Saso\Presentation\Http\Problem;

use Psr\Log\LoggerInterface;
use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;
use Throwable;

/**
 * Single termination point for every uncaught throwable.
 *
 * Behaviour matrix:
 *
 *   - `DomainException`  → response carries the subclass's `ErrorCode` and
 *                          its message verbatim. The full `context()` array
 *                          is logged but never serialised into the body.
 *   - any other throwable → response carries `SASO-INFRA-9000` and either
 *                          a generic message (production) or the original
 *                          message (debug mode). The full stack is logged.
 *
 * In every case the response body carries a freshly-generated `traceId`;
 * the same id appears in the log entry under the `traceId` key, which is
 * what operators use to correlate a support report to a server-side trace.
 */
final class ProblemExceptionHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ProblemRenderer $renderer,
        private readonly bool $debug = false,
        private readonly ?string $typeBaseUrl = null,
    ) {
    }

    public function handle(Throwable $exception, string $instance): ProblemDetails
    {
        $traceId = TraceId::generate();

        if ($exception instanceof DomainException) {
            $code   = $exception->errorCode();
            $detail = $exception->getMessage();
            $logCtx = $exception->context();
        } else {
            $code   = ErrorCode::InfraUnhandled;
            $detail = $this->debug
                ? $exception->getMessage()
                : 'An unexpected error occurred. Reference: '.$traceId;
            $logCtx = [];
        }

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
            title: $code->defaultTitle(),
            detail: $detail,
            instance: $instance,
            traceId: $traceId,
            typeBaseUrl: $this->typeBaseUrl,
        );

        $this->renderer->emit($problem);

        return $problem;
    }
}
