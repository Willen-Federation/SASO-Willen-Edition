<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Messaging\Transport;

use Doctrine\DBAL\DriverManager;
use Saso\Domain\Messaging\Message\IndexItem;
use Saso\Domain\Messaging\Message\Message;
use Saso\Domain\Messaging\Message\ProcessItemDraft;
use Saso\Domain\Setting\SettingKey;
use Saso\Domain\Setting\SystemSettingService;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\Connection as DoctrineMessengerConnection;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransport;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

final class TransportFactory
{
    /**
     * Default allowlist of message classes the worker is permitted to
     * decode off a tampered transport. New domain messages must be added
     * here to round-trip through the queue.
     *
     * @var list<class-string<Message>>
     */
    private const DEFAULT_ALLOWED_MESSAGES = [
        IndexItem::class,
        ProcessItemDraft::class,
    ];

    public function __construct(
        private readonly SystemSettingService $settings,
        private readonly \PDO $pdo,
    ) {
    }

    public function create(): TransportInterface
    {
        $transportSetting = $this->settings->get(new SettingKey('messaging.transport'));
        $transport = $transportSetting !== null ? $transportSetting->raw : 'doctrine';

        if ($transport === 'doctrine') {
            return $this->createDoctrineTransport();
        }

        return new InMemoryTransport();
    }

    /**
     * Builds the serializer applied to every transport that persists
     * payloads outside the application process (currently: Doctrine).
     *
     * The in-memory transport is exempt because the payload never leaves
     * the PHP process — there is no surface for tampering.
     */
    public static function createSerializer(): SerializerInterface
    {
        return new AllowlistedClassesSerializer(new PhpSerializer(), self::DEFAULT_ALLOWED_MESSAGES);
    }

    private function createDoctrineTransport(): DoctrineTransport
    {
        $dbalConnection = DriverManager::getConnection(['pdo' => $this->pdo]);
        $messengerConnection = new DoctrineMessengerConnection([], $dbalConnection);

        return new DoctrineTransport($messengerConnection, self::createSerializer());
    }
}
