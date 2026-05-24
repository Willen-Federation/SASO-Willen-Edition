<?php

declare(strict_types=1);

namespace Saso\Domain\Feature\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;
use Throwable;

/**
 * Thrown by the feature-flag controllers when the request payload (key,
 * rolloutPercent, errorThreshold, …) violates the domain invariants
 * enforced by {@see \Saso\Domain\Feature\FeatureFlag::__construct()} or
 * {@see \Saso\Domain\Feature\FeatureKey::__construct()}.
 *
 * Without this wrapper the underlying `InvalidArgumentException` bubbles up
 * to `ProblemExceptionHandler` as `SASO-INFRA-9000` (500), masking what was
 * really a client mistake. Carrying the code explicitly lands the response
 * on `MobileInvalidRequest` (400) so admins see "the input is wrong" and
 * not "the server is broken".
 */
final class InvalidFlagInputException extends DomainException
{
    public static function fromMessage(string $message, ?Throwable $previous = null): self
    {
        return new self(ErrorCode::MobileInvalidRequest, $message, [], $previous);
    }
}
