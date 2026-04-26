<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Plugin\Registry;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Plugin\Registry\Exception\RegistryCollisionException;
use Saso\Domain\Plugin\Registry\RegistryName;
use Saso\Domain\Shared\ErrorCode;
use Saso\Infrastructure\Ai\NullAssistant;
use Saso\Infrastructure\Plugin\Registry\InMemoryAiAssistantRegistry;

final class InMemoryAiAssistantRegistryTest extends TestCase
{
    public function testStartsEmpty(): void
    {
        $r = new InMemoryAiAssistantRegistry();

        self::assertSame([], $r->names());
        self::assertFalse($r->has(new RegistryName('null')));
        self::assertNull($r->get(new RegistryName('null')));
    }

    public function testRegisterCoreSeeds(): void
    {
        $r = new InMemoryAiAssistantRegistry();
        $r->registerCore(new RegistryName('null'), new NullAssistant());

        self::assertTrue($r->has(new RegistryName('null')));
        self::assertNotNull($r->get(new RegistryName('null')));
    }

    public function testPluginCanRegisterVendorPrefixedName(): void
    {
        $r = new InMemoryAiAssistantRegistry();
        $r->register(new RegistryName('acme:custom'), new NullAssistant());

        self::assertTrue($r->has(new RegistryName('acme:custom')));
    }

    public function testPluginCannotRegisterReservedName(): void
    {
        $r = new InMemoryAiAssistantRegistry();
        $r->registerCore(new RegistryName('openai'), new NullAssistant());

        try {
            $r->register(new RegistryName('openai'), new NullAssistant());
            self::fail('expected RegistryCollisionException');
        } catch (RegistryCollisionException $ex) {
            self::assertSame(ErrorCode::PluginRegistryCollision, $ex->errorCode());
            self::assertSame('ai_assistant', $ex->context()['registry']);
            self::assertSame('openai', $ex->context()['name']);
        }
    }

    public function testPluginCanReregisterItsOwnVendorName(): void
    {
        $r = new InMemoryAiAssistantRegistry();
        $first  = new NullAssistant();
        $second = new NullAssistant();

        $r->register(new RegistryName('acme:custom'), $first);
        $r->register(new RegistryName('acme:custom'), $second);  // overwrite OK

        self::assertSame($second, $r->get(new RegistryName('acme:custom')));
    }

    public function testRegisterCoreNeverThrowsOnCollision(): void
    {
        // The composition root may seed the same core name twice during
        // hot-reload; this must be idempotent.
        $r = new InMemoryAiAssistantRegistry();
        $r->registerCore(new RegistryName('null'), new NullAssistant());
        $r->registerCore(new RegistryName('null'), new NullAssistant());

        self::assertTrue($r->has(new RegistryName('null')));
    }

    public function testReservedRegistrationOnEmptyRegistryIsAllowed(): void
    {
        // The collision rule fires only when the reserved name is
        // already taken. Pre-empting the seed (admittedly unusual) is
        // permitted.
        $r = new InMemoryAiAssistantRegistry();
        $r->register(new RegistryName('openai'), new NullAssistant());

        self::assertTrue($r->has(new RegistryName('openai')));
    }

    public function testUnregisterRemoves(): void
    {
        $r = new InMemoryAiAssistantRegistry();
        $r->register(new RegistryName('acme:custom'), new NullAssistant());
        $r->unregister(new RegistryName('acme:custom'));

        self::assertFalse($r->has(new RegistryName('acme:custom')));
    }

    public function testNamesReturnsRegistryNameValueObjects(): void
    {
        $r = new InMemoryAiAssistantRegistry();
        $r->registerCore(new RegistryName('null'), new NullAssistant());
        $r->register(new RegistryName('acme:custom'), new NullAssistant());

        $names = $r->names();
        self::assertCount(2, $names);
        foreach ($names as $name) {
            self::assertInstanceOf(RegistryName::class, $name);
        }
    }
}
