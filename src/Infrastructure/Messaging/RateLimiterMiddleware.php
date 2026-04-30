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
        $limit = $rateLimitSetting !== null ? (int) $rateLimitSetting->raw : self::DEFAULT_RATE_LIMIT;

        $key = 'saso_msg_rate_'.date('YmdHi');

        $count = apcu_fetch($key);
        if ($count === false) {
            apcu_store($key, 1, 60);
        } else {
            if ($count >= $limit) {
                throw new UnrecoverableMessageHandlingException('Rate limit exceeded');
            }
            apcu_inc($key);
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
