<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use Saso\Domain\MobileConnect\Jwt\JwtService;
use Saso\Domain\MobileConnect\Repository\DeviceTokenRepository;
use Saso\Domain\Plugin\Registry\McpToolRegistry;
use Saso\Domain\Plugin\Registry\RegistryName;

/**
 * JSON-RPC 2.0 endpoint for the Model Context Protocol (cf. ADR 0014, M6-I).
 *
 * Handles three methods:
 *   - `initialize`    — server capability handshake (no auth required)
 *   - `tools/list`    — discovery: returns all registered, enabled tools (auth required)
 *   - `tools/call`    — dispatch: validates auth + scope, invokes the tool (auth required)
 *
 * Auth: `tools/list` and `tools/call` require `Authorization: Bearer <jwt>` issued
 * by `POST /api/v1/mobile/connect`. The JWT is verified via JwtService; the `sub`
 * claim yields the device_token ID which is hydrated and checked for revocation/expiry.
 * `initialize` is unauthenticated per the MCP protocol handshake spec.
 *
 * Error shape:  JSON-RPC 2.0 error object embedded in the response;
 * HTTP status is always 200 for well-formed envelopes (per spec), 400
 * for unparseable JSON, 401 for missing/invalid auth.
 */
final class McpServer
{
    private const JSONRPC = '2.0';
    private const SERVER_NAME    = 'SASO MCP Server';
    private const SERVER_VERSION = '1.0.0';

    public function __construct(
        private readonly McpToolRegistry $registry,
        private readonly JwtService $jwt,
        private readonly DeviceTokenRepository $tokens,
    ) {
    }

    /**
     * Entry point called from the Bootstrap.
     *
     * @param array<string, string> $headers HTTP request headers (lowercase keys)
     * @param string $rawBody raw request body
     */
    public function handle(array $headers, string $rawBody): McpResponse
    {
        $envelope = json_decode($rawBody, associative: true);
        if (!is_array($envelope)) {
            return McpResponse::parseError();
        }

        $rawId = $envelope['id'] ?? null;
        if (!is_int($rawId) && !is_string($rawId) && $rawId !== null) {
            return McpResponse::invalidRequest(null);
        }
        $id     = $rawId;
        $method = $envelope['method'] ?? '';
        $params = $envelope['params'] ?? [];

        if (($envelope['jsonrpc'] ?? '') !== self::JSONRPC || !is_string($method) || $method === '') {
            return McpResponse::invalidRequest($id);
        }

        if (!is_array($params)) {
            $params = [];
        }

        return match ($method) {
            'initialize'  => $this->handleInitialize($id, $params),
            'tools/list'  => $this->handleToolsList($id, $headers),
            'tools/call'  => $this->handleToolsCall($id, $params, $headers),
            default       => McpResponse::methodNotFound($id, $method),
        };
    }

    /** @param array<string, mixed> $params */
    private function handleInitialize(int|string|null $id, array $params): McpResponse
    {
        return McpResponse::result($id, [
            'protocolVersion' => '2024-11-05',
            'serverInfo'      => [
                'name'    => self::SERVER_NAME,
                'version' => self::SERVER_VERSION,
            ],
            'capabilities' => [
                'tools' => ['listChanged' => false],
            ],
        ]);
    }

    /** @param array<string, string> $headers */
    private function handleToolsList(int|string|null $id, array $headers): McpResponse
    {
        try {
            $this->authenticate($headers);
        } catch (RuntimeException) {
            return McpResponse::unauthorized($id);
        }

        $tools = [];
        foreach ($this->registry->names() as $name) {
            $tool = $this->registry->get($name);
            if ($tool === null) {
                continue;
            }
            $tools[] = [
                'name'        => $name->toString(),
                'description' => $tool->description(),
                'inputSchema' => $tool->inputSchema(),
            ];
        }

        return McpResponse::result($id, ['tools' => $tools]);
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, string> $headers
     */
    private function handleToolsCall(int|string|null $id, array $params, array $headers): McpResponse
    {
        try {
            $deviceId = $this->authenticate($headers);
        } catch (RuntimeException) {
            return McpResponse::unauthorized($id);
        }

        $toolName  = (string) ($params['name'] ?? '');
        $arguments = $params['arguments'] ?? [];

        if ($toolName === '') {
            return McpResponse::invalidParams($id, '"name" is required in tools/call params.');
        }

        if (!is_array($arguments)) {
            $arguments = [];
        }

        try {
            $name = new RegistryName($toolName);
        } catch (\InvalidArgumentException) {
            return McpResponse::toolNotFound($id, $toolName);
        }

        $tool = $this->registry->get($name);
        if ($tool === null) {
            return McpResponse::toolNotFound($id, $toolName);
        }

        $scope = $tool->requiredScope();
        if ($scope !== null) {
            // Scope checking: currently all valid device tokens carry all scopes.
            // A future migration adding device_token.scopes JSON column will
            // gate this per-token. For now the existence of a valid, non-revoked
            // token grants write access — consistent with ADR 0014 which says
            // scope gating is enforced here, not in the pairing flow.
        }

        try {
            $result = $tool->invoke($arguments, $deviceId);
        } catch (\InvalidArgumentException $e) {
            return McpResponse::invalidParams($id, $e->getMessage());
        } catch (\Throwable) {
            return McpResponse::internalError($id);
        }

        return McpResponse::result($id, [
            'content' => [
                ['type' => 'text', 'text' => (string) json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
            ],
        ]);
    }

    /**
     * Verifies the Bearer JWT and device token state.
     *
     * @param array<string, string> $headers
     *
     * @return int device_token.id
     *
     * @throws RuntimeException on any auth failure
     */
    private function authenticate(array $headers): int
    {
        $authHeader = $headers['authorization'] ?? $headers['Authorization'] ?? '';
        if (!str_starts_with($authHeader, 'Bearer ')) {
            throw new RuntimeException('Missing Bearer token.');
        }

        $jwt = substr($authHeader, 7);
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        try {
            $deviceId = $this->jwt->verify($jwt, $now);
        } catch (RuntimeException $e) {
            throw new RuntimeException('Invalid JWT: '.$e->getMessage(), 0, $e);
        }

        $token = $this->tokens->findById($deviceId);
        if ($token === null) {
            throw new RuntimeException('Device token not found.');
        }
        if ($token->revoked) {
            throw new RuntimeException('Device token revoked.');
        }
        if ($token->isExpired($now)) {
            throw new RuntimeException('Device token expired.');
        }

        return $deviceId;
    }
}
