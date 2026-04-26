<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Plugin\Registry;

use Saso\Domain\Mcp\McpTool;
use Saso\Domain\Plugin\Registry\Exception\RegistryCollisionException;
use Saso\Domain\Plugin\Registry\McpToolRegistry;
use Saso\Domain\Plugin\Registry\RegistryName;

/**
 * In-process {@see McpToolRegistry}. Same reserved-vs-vendor rules
 * as {@see InMemoryAiAssistantRegistry}.
 */
final class InMemoryMcpToolRegistry implements McpToolRegistry
{
    private const REGISTRY_LABEL = 'mcp_tool';

    /** @var array<string, McpTool> */
    private array $byName = [];

    public function register(RegistryName $name, McpTool $tool): void
    {
        if ($name->isReserved() && isset($this->byName[$name->toString()])) {
            throw RegistryCollisionException::for(self::REGISTRY_LABEL, $name);
        }
        $this->byName[$name->toString()] = $tool;
    }

    public function registerCore(RegistryName $name, McpTool $tool): void
    {
        $this->byName[$name->toString()] = $tool;
    }

    public function unregister(RegistryName $name): void
    {
        unset($this->byName[$name->toString()]);
    }

    public function get(RegistryName $name): ?McpTool
    {
        return $this->byName[$name->toString()] ?? null;
    }

    public function has(RegistryName $name): bool
    {
        return isset($this->byName[$name->toString()]);
    }

    public function names(): array
    {
        $names = [];
        foreach (array_keys($this->byName) as $name) {
            $names[] = new RegistryName($name);
        }

        return $names;
    }
}
