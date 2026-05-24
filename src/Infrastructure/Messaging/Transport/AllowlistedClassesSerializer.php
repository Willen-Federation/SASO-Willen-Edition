<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Messaging\Transport;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * Wraps another {@see SerializerInterface} and rejects any decoded envelope
 * whose message is not an instance of one of the configured allowlist
 * classes.
 *
 * Defends against PHP object injection in transports that persist
 * payloads we do not fully control (e.g. the Doctrine `messenger_messages`
 * table, which may be writable by a co-tenant on shared hosting). Without
 * the allowlist, the underlying {@see \Symfony\Component\Messenger\Transport\Serialization\PhpSerializer}
 * happily unserialises any autoloadable class — opening the door to POP
 * gadget chains in installed dependencies.
 */
final class AllowlistedClassesSerializer implements SerializerInterface
{
    /**
     * @param list<class-string> $allowedClasses messages whose FQCN is not on
     *                                           this list (or a subclass of one of
     *                                           them) are rejected at decode time.
     */
    public function __construct(
        private readonly SerializerInterface $inner,
        private readonly array $allowedClasses,
    ) {
    }

    public function decode(array $encodedEnvelope): Envelope
    {
        $envelope = $this->inner->decode($encodedEnvelope);
        $message  = $envelope->getMessage();

        foreach ($this->allowedClasses as $allowed) {
            if ($message instanceof $allowed) {
                return $envelope;
            }
        }

        throw new MessageDecodingFailedException(sprintf(
            'Refusing to decode message of class "%s" — not in the configured allowlist.',
            $message::class,
        ));
    }

    public function encode(Envelope $envelope): array
    {
        return $this->inner->encode($envelope);
    }
}
