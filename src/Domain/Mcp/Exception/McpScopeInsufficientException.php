<?php

declare(strict_types=1);

namespace Saso\Domain\Mcp\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

final class McpScopeInsufficientException extends DomainException
{
    public function __construct(string $requiredScope)
    {
        parent::__construct(
            errorCode: ErrorCode::McpScopeInsufficient,
            message: sprintf('This MCP tool requires the "%s" scope.', $requiredScope),
            context: ['requiredScope' => $requiredScope],
        );
    }
}
