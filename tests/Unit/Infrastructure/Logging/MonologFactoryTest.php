<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Logging;

use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;
use Saso\Infrastructure\Logging\MonologFactory;

final class MonologFactoryTest extends TestCase
{
    public function testWithHandlerProducesNamedLogger(): void
    {
        $logger = MonologFactory::withHandler(new TestHandler(), 'saso-test');

        self::assertSame('saso-test', $logger->getName());
    }

    public function testTraceIdProcessorIsAttached(): void
    {
        $handler = new TestHandler();
        $logger  = MonologFactory::withHandler($handler, 'saso-test');

        $logger->error('boom', ['traceId' => 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee']);

        $record = $handler->getRecords()[0];

        self::assertArrayHasKey('traceId', $record->extra);
        self::assertSame(
            'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee',
            $record->extra['traceId'],
        );
    }

    public function testTraceIdProcessorIgnoresMissingId(): void
    {
        $handler = new TestHandler();
        $logger  = MonologFactory::withHandler($handler, 'saso-test');

        $logger->error('boom', ['user_id' => 42]);

        $record = $handler->getRecords()[0];

        self::assertArrayNotHasKey('traceId', $record->extra);
    }
}
