<?php

declare(strict_types=1);

namespace Saso\Domain\Shared;

use RuntimeException;
use Throwable;

/**
 * Base class for every exception that maps to a SASO error code.
 *
 * Domain code throws subclasses; the global exception handler
 * (`ProblemExceptionHandler`) inspects `errorCode()` to build the RFC 7807
 * response. Anything that does not extend this class is treated as
 * `SASO-INFRA-9000` and logged with full stack — the response carries only
 * the trace identifier, never internals.
 *
 * The `context` array is logged but never serialised into the response. It
 * is the right place for query parameters, IDs, or other server-side
 * breadcrumbs that help an operator find a request in the log.
 */
abstract class DomainException extends RuntimeException
{
    /**
     * @param array<string, mixed> $context arbitrary breadcrumbs for the log
     */
    public function __construct(
        private readonly ErrorCode $errorCode,
        string $message = '',
        private readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            $message !== '' ? $message : $errorCode->defaultTitle(),
            0,
            $previous,
        );
    }

    public function errorCode(): ErrorCode
    {
        return $this->errorCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }
}
