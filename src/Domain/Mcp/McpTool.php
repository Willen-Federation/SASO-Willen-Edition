<?php

declare(strict_types=1);

namespace Saso\Domain\Mcp;

/**
 * Marker interface for MCP tools (cf. ADR 0014).
 *
 * In M6-J2 this is a marker only — `McpToolRegistry` accepts and
 * dispatches anything implementing it. The full `McpTool`
 * lifecycle (`name`, `inputSchema`, `invoke`, `description`,
 * scope-gating) lands in M6-I when the JSON-RPC `/mcp` endpoint
 * goes live; the registry shape is stable now so plugins can
 * register tools today.
 */
interface McpTool
{
}
