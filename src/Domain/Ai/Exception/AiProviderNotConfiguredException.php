<?php

declare(strict_types=1);

namespace Saso\Domain\Ai\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

/**
 * Thrown when an AI operation is invoked but no API key is configured
 * for the chosen provider — typically because the operator has not
 * yet entered their key in the admin settings, or because `SAFE_MODE`
 * forces `NullAssistant`.
 *
 * The HTTP shape is 503 (the AI feature is currently unavailable).
 */
final class AiProviderNotConfiguredException extends DomainException
{
    public static function for(string $providerName, string $operation): self
    {
        return new self(
            ErrorCode::AiProviderNotConfigured,
            sprintf(
                'AI provider "%s" is not configured for operation "%s". Ask an administrator to register the API key.',
                $providerName,
                $operation,
            ),
            ['provider' => $providerName, 'operation' => $operation],
        );
    }
}
