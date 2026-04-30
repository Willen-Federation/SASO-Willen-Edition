<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Messaging\Transport;

use Doctrine\DBAL\DriverManager;
use Saso\Domain\Setting\SettingKey;
use Saso\Domain\Setting\SystemSettingService;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\Connection as DoctrineMessengerConnection;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransport;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\TransportInterface;

final class TransportFactory
{
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

    private function createDoctrineTransport(): DoctrineTransport
    {
        $dbalConnection = DriverManager::getConnection(['pdo' => $this->pdo]);
        $messengerConnection = new DoctrineMessengerConnection([], $dbalConnection);
        $serializer = new PhpSerializer();

        return new DoctrineTransport($messengerConnection, $serializer);
    }
}
