<?php

declare(strict_types=1);

namespace Saso\Domain\Plugin\Registry;

use Saso\Domain\Ai\AiAssistant;

/**
 * Plugin extension point for adding new {@see AiAssistant}
 * implementations beyond the core OpenAI / Gemini / Claude / Null set
 * (cf. ADR 0015 + ADR 0009).
 *
 * Plugins call {@see register()} from `Plugin::register()`. The
 * `RegistryName` becomes selectable in `system_setting`
 * (`ai.provider.chat = acme:custom-llm`); the `AssistantRouter`
 * (M6-F) reads the registry to resolve the configured name to an
 * `AiAssistant` instance.
 *
 * Plugins cannot replace core entries — `register()` throws
 * `RegistryCollisionException` if the name is reserved
 * ({@see RegistryName::isReserved()}). Plugins owning their entries
 * may freely overwrite (e.g. to swap a vendor implementation between
 * mock and live during testing).
 */
interface AiAssistantRegistry
{
    /**
     * @throws \Saso\Domain\Plugin\Registry\Exception\RegistryCollisionException
     */
    public function register(RegistryName $name, AiAssistant $assistant): void;

    public function unregister(RegistryName $name): void;

    public function get(RegistryName $name): ?AiAssistant;

    public function has(RegistryName $name): bool;

    /**
     * @return list<RegistryName>
     */
    public function names(): array;
}
