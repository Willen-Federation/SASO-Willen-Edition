<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Ai;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Ai\ChatMessage;
use Saso\Domain\Ai\ChatRole;

final class ChatMessageTest extends TestCase
{
    public function testFactoriesProduceTypedRoles(): void
    {
        self::assertSame(ChatRole::System, ChatMessage::system('s')->role);
        self::assertSame(ChatRole::User, ChatMessage::user('u')->role);
        self::assertSame(ChatRole::Assistant, ChatMessage::assistant('a')->role);
    }

    public function testRejectsEmptyContent(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ChatMessage(ChatRole::User, '');
    }

    public function testEnumValuesMatchVendorWire(): void
    {
        self::assertSame('system', ChatRole::System->value);
        self::assertSame('user', ChatRole::User->value);
        self::assertSame('assistant', ChatRole::Assistant->value);
        self::assertSame('tool', ChatRole::Tool->value);
    }
}
