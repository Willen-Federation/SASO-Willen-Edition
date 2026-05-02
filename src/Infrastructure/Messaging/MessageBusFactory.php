<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Messaging;

use Symfony\Component\Messenger\Handler\HandlerDescriptor;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

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
}
