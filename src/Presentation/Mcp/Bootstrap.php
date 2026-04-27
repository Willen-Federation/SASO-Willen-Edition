<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp;

use PDO;
use Saso\Domain\MobileConnect\Jwt\JwtService;
use Saso\Domain\Plugin\Registry\RegistryName;
use Saso\Infrastructure\MobileConnect\PdoDeviceTokenRepository;
use Saso\Infrastructure\Plugin\Registry\InMemoryMcpToolRegistry;
use Saso\Infrastructure\Search\NullSearchIndex;
use Saso\Infrastructure\StorageLocation\PdoStorageLocationRepository;
use Saso\Presentation\Mcp\Tool\GetItemTool;
use Saso\Presentation\Mcp\Tool\ListStorageLocationsTool;
use Saso\Presentation\Mcp\Tool\RegisterItemTool;
use Saso\Presentation\Mcp\Tool\SearchItemsTool;

/**
 * Composition root for `POST /mcp`.
 *
 * Wires the four core MCP tools, the JWT service, and the device-token
 * repository into the McpServer, then dispatches the current request.
 */
final class Bootstrap
{
    public static function dispatch(): void
    {
        $pdo = self::createPdo();
        $jwt = new JwtService(self::jwtSecret());

        $tokenRepo = new PdoDeviceTokenRepository($pdo);
        $locations = new PdoStorageLocationRepository($pdo);
        $search    = new NullSearchIndex();

        $registry = new InMemoryMcpToolRegistry();
        $registry->registerCore(new RegistryName('search_items'), new SearchItemsTool($search));
        $registry->registerCore(new RegistryName('get_item'), new GetItemTool($pdo));
        $registry->registerCore(new RegistryName('list_storage_locations'), new ListStorageLocationsTool($locations));
        $registry->registerCore(new RegistryName('register_item'), new RegisterItemTool($pdo));

        $server = new McpServer($registry, $jwt, $tokenRepo);

        $headers = self::collectHeaders();
        $body    = (string) file_get_contents('php://input');

        $response = $server->handle($headers, $body);
        $response->emit();
    }

    private static function createPdo(): PDO
    {
        if (class_exists(\saso\repository\DBConnection::class)) {
            return \saso\repository\DBConnection::getPdo();
        }

        $config = \saso\ConfigLoader::load();
        $db     = $config['database'];

        return new PDO(
            (string) ($db['dsn'] ?? ''),
            (string) ($db['user'] ?? ''),
            (string) ($db['password'] ?? ''),
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ],
        );
    }

    private static function jwtSecret(): string
    {
        $jwtSecret = getenv('JWT_SECRET');
        if (is_string($jwtSecret) && strlen($jwtSecret) >= 32) {
            return $jwtSecret;
        }

        $appKey = getenv('APP_KEY');
        if (is_string($appKey) && $appKey !== '') {
            return hash('sha256', $appKey, binary: true);
        }

        $config = \saso\ConfigLoader::load();
        $dsn    = (string) ($config['database']['dsn'] ?? 'saso-fallback');

        return hash('sha256', 'saso-jwt-'.$dsn, binary: true);
    }

    /** @return array<string, string> */
    private static function collectHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (!str_starts_with($key, 'HTTP_')) {
                continue;
            }
            $name           = strtolower(str_replace('_', '-', substr($key, 5)));
            $headers[$name] = (string) $value;
        }

        return $headers;
    }
}
