<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Plugin\Registry;

use Saso\Domain\Ai\AiAssistant;
use Saso\Domain\Plugin\Registry\AiAssistantRegistry;
use Saso\Domain\Plugin\Registry\Exception\RegistryCollisionException;
use Saso\Domain\Plugin\Registry\RegistryName;

/**
 * In-process {@see AiAssistantRegistry}. The only production
 * implementation — registries live for the duration of one PHP
 * request and rebuild from `vendor/composer/installed.json` on each
 * cold boot via the M6-J2 `PluginDiscovery` (next PR).
 *
 * Reserved-name collision rules per ADR 0015:
 *
 *   * Names without a `:` (i.e. `RegistryName::isReserved() === true`)
 *     are core-only. Plugins attempting to `register()` against them
 *     get `RegistryCollisionException`.
 *   * Vendor-prefixed names (`acme:custom-llm`) can be registered
 *     and re-registered freely — overwriting your own registration
 *     is supported (useful for tests that swap a vendor between
 *     mock and live).
 *
 * The composition root pre-populates the registry with the core
 * names (`openai`, `gemini`, `claude`, `null`) before plugin
 * discovery runs; subsequent plugin attempts to claim those names
 * fail loudly.
 */
final class InMemoryAiAssistantRegistry implements AiAssistantRegistry
{
    private const REGISTRY_LABEL = 'ai_assistant';

    /** @var array<string, AiAssistant> */
    private array $byName = [];

    public function register(RegistryName $name, AiAssistant $assistant): void
    {
        if ($name->isReserved() && isset($this->byName[$name->toString()])) {
            // Reserved name already registered (by core). Plugins must
            // not displace core entries.
            throw RegistryCollisionException::for(self::REGISTRY_LABEL, $name);
        }
        $this->byName[$name->toString()] = $assistant;
    }

    /**
     * Core composition root uses this to seed reserved names without
     * tripping the plugin-collision rule. Idempotent.
     */
    public function registerCore(RegistryName $name, AiAssistant $assistant): void
    {
        $this->byName[$name->toString()] = $assistant;
    }

    public function unregister(RegistryName $name): void
    {
        unset($this->byName[$name->toString()]);
    }

    public function get(RegistryName $name): ?AiAssistant
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
