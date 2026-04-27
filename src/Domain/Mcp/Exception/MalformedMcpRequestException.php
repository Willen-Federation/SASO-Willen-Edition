<?php

declare(strict_types=1);

namespace Saso\Domain\Mcp\Exception;

use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;

final class MalformedMcpRequestException extends DomainException
{
    public function __construct(string $reason = '')
    {
        parent::__construct(
            errorCode: ErrorCode::McpMalformedRequest,
            message: $reason !== '' ? $reason : 'MCP JSON-RPC envelope is invalid.',
            context: ['reason' => $reason],
        );
    }
}
