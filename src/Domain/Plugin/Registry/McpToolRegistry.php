<?php

declare(strict_types=1);

namespace Saso\Domain\Plugin\Registry;

use Saso\Domain\Mcp\McpTool;

/**
 * Plugin extension point for adding new {@see McpTool}
 * implementations beyond the core `search_items` / `get_item` /
 * `list_storage_locations` / `register_item` set
 * (cf. ADR 0015 + ADR 0014).
 *
 * Names follow the same reserved-vs-vendor convention. Core tool
 * names (without `:`) are seeded by the M6-I composition root;
 * plugins extend the surface with vendor-prefixed tools that show
 * up in the MCP `tools/list` discovery response.
 */
interface McpToolRegistry
{
    /**
     * @throws \Saso\Domain\Plugin\Registry\Exception\RegistryCollisionException
     */
    public function register(RegistryName $name, McpTool $tool): void;

    public function unregister(RegistryName $name): void;

    public function get(RegistryName $name): ?McpTool;

    public function has(RegistryName $name): bool;

    /**
     * @return list<RegistryName>
     */
    public function names(): array;
}
