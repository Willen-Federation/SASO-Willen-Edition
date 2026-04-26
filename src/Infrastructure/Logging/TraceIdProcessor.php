<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Monolog processor that copies a `traceId` from the log record's context
 * into its top-level `extra` block.
 *
 * Most handlers serialise `extra` separately from `context`. Promoting
 * `traceId` keeps it visible in compact log lines (line-formatter) and in
 * structured sinks alike, without forcing every call site to repeat the
 * field in two places.
 */
final class TraceIdProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $traceId = $record->context['traceId'] ?? null;

        if (is_string($traceId) && $traceId !== '') {
            return $record->with(extra: $record->extra + ['traceId' => $traceId]);
        }

        return $record;
    }
}
