<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Messaging;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Messaging\Message\IndexItem;
use Saso\Domain\Setting\SettingKey;
use Saso\Domain\Setting\SettingValue;
use Saso\Domain\Setting\SystemSettingService;
use Saso\Infrastructure\Messaging\RateLimiterMiddleware;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class RateLimiterMiddlewareTest extends TestCase
{
    public function testPassesThroughWhenApcuUnavailable(): void
    {
        if (function_exists('apcu_fetch')) {
            self::markTestSkipped('Test only verifies the no-APCu fallback.');
        }

        $middleware = new RateLimiterMiddleware($this->settingsReturning(null));

        $envelope = new Envelope(new IndexItem(itemId: 1));
        $stack    = $this->stackThatReturns($envelope);

        self::assertSame($envelope, $middleware->handle($envelope, $stack));
    }

    public function testNonPositiveLimitPassesThrough(): void
    {
        if (!function_exists('apcu_fetch')) {
            self::markTestSkipped('Requires APCu to exercise the post-fetch branch.');
        }

        // A misconfigured limit ("" cast to 0, or an explicit "0") must not
        // silently drop every message — that would be the worst possible
        // failure mode for a typo in admin settings.
        $middleware = new RateLimiterMiddleware($this->settingsReturning(
            new SettingValue('0', \Saso\Domain\Setting\SettingType::Int),
        ));

        $envelope = new Envelope(new IndexItem(itemId: 1));
        $stack    = $this->stackThatReturns($envelope);

        self::assertSame($envelope, $middleware->handle($envelope, $stack));
    }

    public function testThrowsOnceLimitIsExceeded(): void
    {
        if (!function_exists('apcu_fetch') || !function_exists('apcu_clear_cache')) {
            self::markTestSkipped('Requires APCu (and APCu CLI mode) to drive the counter.');
        }

        apcu_clear_cache();

        $middleware = new RateLimiterMiddleware($this->settingsReturning(
            new SettingValue('2', \Saso\Domain\Setting\SettingType::Int),
        ));

        $envelope = new Envelope(new IndexItem(itemId: 1));

        // Two dispatches succeed; the third must throw.
        $middleware->handle($envelope, $this->stackThatReturns($envelope));
        $middleware->handle($envelope, $this->stackThatReturns($envelope));

        $this->expectException(UnrecoverableMessageHandlingException::class);
        $this->expectExceptionMessage('Rate limit exceeded');

        $middleware->handle($envelope, $this->stackThatReturns($envelope));
    }

    private function settingsReturning(?SettingValue $value): SystemSettingService
    {
        return new class ($value) implements SystemSettingService {
            public function __construct(private readonly ?SettingValue $value)
            {
            }

            public function get(SettingKey $key): ?SettingValue
            {
                return $this->value;
            }

            public function require(SettingKey $key): SettingValue
            {
                throw new \LogicException('not used');
            }

            public function set(SettingKey $key, SettingValue $value, string $changedBy, ?string $reason = null): void
            {
                throw new \LogicException('not used');
            }

            public function delete(SettingKey $key, string $changedBy, ?string $reason = null): void
            {
                throw new \LogicException('not used');
            }

            public function all(): array
            {
                return [];
            }
        };
    }

    private function stackThatReturns(Envelope $envelope): StackInterface
    {
        return new class ($envelope) implements StackInterface {
            public function __construct(private readonly Envelope $envelope)
            {
            }

            public function next(): \Symfony\Component\Messenger\Middleware\MiddlewareInterface
            {
                return new class ($this->envelope) implements \Symfony\Component\Messenger\Middleware\MiddlewareInterface {
                    public function __construct(private readonly Envelope $envelope)
                    {
                    }

                    public function handle(Envelope $envelope, StackInterface $stack): Envelope
                    {
                        return $this->envelope;
                    }
                };
            }
        };
    }
}
