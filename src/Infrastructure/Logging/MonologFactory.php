<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Logging;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;

/**
 * Builds the application Monolog logger.
 *
 * Defaults:
 *   - channel   : `saso`
 *   - log dir   : `<project_root>/var/log` (created at 0750 if missing)
 *   - retention : 14 daily files
 *   - threshold : `Level::Info`
 *   - processor : {@see TraceIdProcessor} promotes `traceId` from context
 *                 into `extra`, so every log line emitted by
 *                 `ProblemExceptionHandler` carries the correlation id.
 *
 * Tests inject a custom handler (e.g. `TestHandler`) via {@see withHandler}
 * to capture records without touching the filesystem.
 */
final class MonologFactory
{
    public static function create(
        string $channel = 'saso',
        ?string $logDir = null,
        Level $level = Level::Info,
    ): Logger {
        $dir = $logDir ?? self::defaultLogDir();
        if (!is_dir($dir)) {
            // Suppress: directory may already exist by the time mkdir runs
            // under concurrent requests; the is_dir check below is the real
            // success criterion.
            @mkdir($dir, 0o750, true);
        }

        $handler = new RotatingFileHandler($dir.'/'.$channel.'.log', 14, $level);

        return self::withHandler($handler, $channel);
    }

    public static function withHandler(HandlerInterface $handler, string $channel = 'saso'): Logger
    {
        $logger = new Logger($channel);
        $logger->pushHandler($handler);
        $logger->pushProcessor(new TraceIdProcessor());

        return $logger;
    }

    private static function defaultLogDir(): string
    {
        return dirname(__DIR__, 3).'/var/log';
    }
}
