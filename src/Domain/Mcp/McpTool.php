<?php

declare(strict_types=1);

namespace Saso\Domain\Mcp;

/**
 * Contract for every MCP tool surfaced by the `/mcp` JSON-RPC endpoint
 * (cf. ADR 0014, M6-I).
 *
 * Tools are registered via {@see \Saso\Domain\Plugin\Registry\McpToolRegistry}
 * and dispatched by {@see \Saso\Presentation\Mcp\McpServer}. The registry
 * accepts plugin-supplied implementations; core tools live in
 * `src/Presentation/Mcp/Tool/`.
 */
interface McpTool
{
    /** Stable tool identifier shown in `tools/list` (e.g. `search_items`). */
    public function name(): string;

    /** One-sentence description returned in `tools/list`. */
    public function description(): string;

    /**
     * JSON Schema (draft-07 compatible) describing the `arguments` object
     * that `tools/call` accepts for this tool.
     *
     * @return array<string, mixed>
     */
    public function inputSchema(): array;

    /**
     * Execute the tool and return a result map.
     *
     * @param array<string, mixed> $input validated `arguments` from the caller
     * @param int $deviceId device_token.id from the verified JWT
     *
     * @return array<string, mixed>
     */
    public function invoke(array $input, int $deviceId): array;

    /**
     * OAuth2 scope required to call this tool, or `null` if no scope check
     * is needed (read-only tools available to any authenticated device).
     *
     * Example: `'items:write'` for {@see \Saso\Presentation\Mcp\Tool\RegisterItemTool}.
     */
    public function requiredScope(): ?string;
}
