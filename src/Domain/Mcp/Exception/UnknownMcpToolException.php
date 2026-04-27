<?php

declare(strict_types=1);

namespace Saso\Domain\Mcp\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

final class UnknownMcpToolException extends DomainException
{
    public function __construct(string $toolName)
    {
        parent::__construct(
            errorCode: ErrorCode::McpUnknownTool,
            message: sprintf('MCP tool "%s" is not registered or is disabled.', $toolName),
            context: ['tool' => $toolName],
        );
    }
}
