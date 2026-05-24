<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Messaging;

use Saso\Domain\Setting\SettingKey;
use Saso\Domain\Setting\SystemSettingService;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class RateLimiterMiddleware implements MiddlewareInterface
{
    private const DEFAULT_RATE_LIMIT = 10;
    private const WINDOW_SECONDS     = 60;

    public function __construct(
        private readonly SystemSettingService $settings,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if (!function_exists('apcu_fetch')) {
            return $stack->next()->handle($envelope, $stack);
        }

        $rateLimitSetting = $this->settings->get(new SettingKey('messaging.rate_limit'));
        $limit = $rateLimitSetting !== null ? $rateLimitSetting->asInt() : self::DEFAULT_RATE_LIMIT;

        // A non-positive limit is treated as misconfiguration rather than as
        // "block everything" — silently dropping every dispatch on a typo
        // (e.g. raw="" or raw="off") would be a worse failure mode than
        // ignoring the limit entirely.
        if ($limit < 1) {
            return $stack->next()->handle($envelope, $stack);
        }

        $key = 'saso_msg_rate_'.date('YmdHi');

        // apcu_inc is atomic when the key exists. To avoid a TOCTOU race
        // where two workers both see "count = limit - 1" and both increment
        // past the limit, we increment first and then compare. apcu_add
        // creates the slot atomically only if absent; if another worker
        // raced in first, apcu_inc returns the now-existing counter.
        if (apcu_add($key, 1, self::WINDOW_SECONDS)) {
            $count = 1;
        } else {
            $success = false;
            $count   = apcu_inc($key, 1, $success);
            if (!$success) {
                // The slot expired between add and inc — re-seed.
                apcu_add($key, 1, self::WINDOW_SECONDS);
                $count = 1;
            }
        }

        if ($count > $limit) {
            throw new UnrecoverableMessageHandlingException('Rate limit exceeded');
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
