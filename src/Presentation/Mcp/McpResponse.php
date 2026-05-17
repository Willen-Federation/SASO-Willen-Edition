<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp;

/**
 * JSON-RPC 2.0 response envelope for the MCP endpoint.
 *
 * Factory methods mirror the standard JSON-RPC error codes:
 *   -32700  Parse error
 *   -32600  Invalid Request
 *   -32601  Method not found
 *   -32602  Invalid params
 *   -32603  Internal error
 *
 * SASO-specific codes:
 *   -32001  Unauthorized (missing / expired / revoked Bearer token)
 *   -32002  Tool not found (SASO-MCP-A001)
 *   -32003  Forbidden: device token lacks the required scope
 */
final readonly class McpResponse
{
    private const JSONRPC = '2.0';

    /**
     * @param array<string, mixed>|null $result
     * @param array<string, mixed>|null $error
     */
    private function __construct(
        public ?int $httpStatus,
        public array|null $result,
        public ?array $error,
        public int|string|null $id,
    ) {
    }

    /** @param array<string, mixed> $result */
    public static function result(int|string|null $id, array $result): self
    {
        return new self(httpStatus: 200, result: $result, error: null, id: $id);
    }

    public static function parseError(): self
    {
        return new self(
            httpStatus: 400,
            result: null,
            error: ['code' => -32700, 'message' => 'Parse error'],
            id: null,
        );
    }

    public static function invalidRequest(int|string|null $id): self
    {
        return new self(
            httpStatus: 200,
            result: null,
            error: ['code' => -32600, 'message' => 'Invalid Request'],
            id: $id,
        );
    }

    public static function methodNotFound(int|string|null $id, string $method): self
    {
        return new self(
            httpStatus: 200,
            result: null,
            error: ['code' => -32601, 'message' => sprintf('Method not found: %s', $method)],
            id: $id,
        );
    }

    public static function invalidParams(int|string|null $id, string $detail = ''): self
    {
        return new self(
            httpStatus: 200,
            result: null,
            error: ['code' => -32602, 'message' => 'Invalid params'.($detail !== '' ? ': '.$detail : '')],
            id: $id,
        );
    }

    public static function internalError(int|string|null $id, string $detail = ''): self
    {
        return new self(
            httpStatus: 200,
            result: null,
            error: ['code' => -32603, 'message' => 'Internal error'.($detail !== '' ? ': '.$detail : '')],
            id: $id,
        );
    }

    public static function unauthorized(int|string|null $id): self
    {
        return new self(
            httpStatus: 401,
            result: null,
            error: ['code' => -32001, 'message' => 'Unauthorized: missing or invalid Bearer token'],
            id: $id,
        );
    }

    public static function toolNotFound(int|string|null $id, string $toolName): self
    {
        return new self(
            httpStatus: 200,
            result: null,
            error: ['code' => -32002, 'message' => sprintf('Tool not found: %s', $toolName)],
            id: $id,
        );
    }

    public static function scopeInsufficient(int|string|null $id, string $requiredScope): self
    {
        return new self(
            httpStatus: 403,
            result: null,
            error: [
                'code'    => -32003,
                'message' => sprintf('Forbidden: device token lacks the "%s" scope.', $requiredScope),
                'data'    => ['requiredScope' => $requiredScope],
            ],
            id: $id,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $envelope = ['jsonrpc' => self::JSONRPC, 'id' => $this->id];

        if ($this->error !== null) {
            $envelope['error'] = $this->error;
        } else {
            $envelope['result'] = $this->result;
        }

        return $envelope;
    }

    public function emit(): void
    {
        $status = $this->httpStatus ?? 200;
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
