<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Application\Messaging;

use Monolog\Handler\TestHandler;
use Monolog\Level;
use PHPUnit\Framework\TestCase;
use Saso\Application\Messaging\Handler\IndexItemHandler;
use Saso\Domain\Messaging\Message\IndexItem;
use Saso\Infrastructure\Logging\MonologFactory;

final class IndexItemHandlerTest extends TestCase
{
    public function testInvokesAreLoggedAtInfoLevel(): void
    {
        $logHandler = new TestHandler();
        $logger     = MonologFactory::withHandler($logHandler);
        $handler    = new IndexItemHandler($logger);

        ($handler)(new IndexItem(itemId: 42, reason: 'manual reindex'));

        self::assertTrue($logHandler->hasInfoRecords());
        $record = $logHandler->getRecords()[0];
        self::assertSame(Level::Info, $record->level);
        self::assertSame('IndexItem received', $record->message);
        self::assertSame(42, $record->context['item_id']);
        self::assertSame('manual reindex', $record->context['reason']);
    }
}
