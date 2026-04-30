<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Messaging;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Messaging\Message\ProcessItemDraft;

final class ProcessItemDraftTest extends TestCase
{
    public function testValidDraftId(): void
    {
        $msg = new ProcessItemDraft(42);
        self::assertSame(42, $msg->draftId);
    }

    public function testZeroIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ProcessItemDraft(0);
    }

    public function testNegativeIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ProcessItemDraft(-1);
    }
}
