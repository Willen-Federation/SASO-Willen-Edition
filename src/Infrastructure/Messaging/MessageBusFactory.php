<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Messaging;

use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * Builds a Symfony Messenger {@see MessageBus} configured for SASO
 * (cf. ADR 0013).
 *
 * Handlers are registered explicitly — message FQCN → list of
 * callables. The application's composition root (M6-C wiring lands
 * with M6-D, when the first real handler exists) constructs the
 * factory with whichever handler instances it needs.
 *
 * The bus carries one middleware in this PR — `HandleMessageMiddleware`,
 * which dispatches the message to its handlers synchronously.
 * Transports (Redis Streams, Doctrine, Sync) are added by
 * {@see TransportFactory} and threaded into the bus when async
 * dispatch is enabled in M6-D.
 *
 * Tests use the `sync` transport behaviour (the default when no
 * transport sender is registered for a message class) to dispatch
 * inline and assert observable side effects on injected fakes.
 */
final class MessageBusFactory
{
    /**
     * @param array<class-string, list<callable>> $handlersByMessage
     */
    public static function create(array $handlersByMessage): MessageBusInterface
    {
        $descriptors = [];
        foreach ($handlersByMessage as $messageClass => $handlers) {
            $descriptors[$messageClass] = array_map(
                static fn (callable $h): HandlerDescriptor => new HandlerDescriptor($h),
                $handlers,
            );
        }

        $locator = new HandlersLocator($descriptors);

        return new MessageBus([
            new HandleMessageMiddleware($locator),
        ]);
    }

    /**
     * @param array<class-string, list<callable>> $handlersByMessage
     */
    public static function createWithTransport(
        array $handlersByMessage,
        ?TransportInterface $transport = null,
        ?MiddlewareInterface ...$extraMiddleware,
    ): MessageBusInterface {
        $descriptors = [];
        foreach ($handlersByMessage as $messageClass => $handlers) {
            $descriptors[$messageClass] = array_map(
                static fn (callable $h): HandlerDescriptor => new HandlerDescriptor($h),
                $handlers,
            );
        }

        $locator = new HandlersLocator($descriptors);

        $middleware = array_values(array_filter($extraMiddleware));

        if ($transport !== null) {
            $sendersMap = ['*' => ['async']];
            $container = new class ($transport) implements ContainerInterface {
                public function __construct(private readonly TransportInterface $transport)
                {
                }

                public function get(string $id): TransportInterface
                {
                    return $this->transport;
                }

                public function has(string $id): bool
                {
                    return $id === 'async';
                }
            };

            $middleware[] = new SendMessageMiddleware(
                new SendersLocator($sendersMap, $container),
            );
        }

        $middleware[] = new HandleMessageMiddleware($locator);

        return new MessageBus($middleware);
    }
}
