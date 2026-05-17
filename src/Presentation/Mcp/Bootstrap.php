<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp;

use PDO;
use Saso\Domain\MobileConnect\Jwt\JwtService;
use Saso\Domain\Plugin\Registry\RegistryName;
use Saso\Infrastructure\Category\PdoCategoryRepository;
use Saso\Infrastructure\Item\Attribute\PdoAttributeDefinitionRepository;
use Saso\Infrastructure\MobileConnect\PdoDeviceTokenRepository;
use Saso\Infrastructure\Plugin\Registry\InMemoryMcpToolRegistry;
use Saso\Infrastructure\Search\NullSearchIndex;
use Saso\Infrastructure\StorageLocation\PdoStorageLocationRepository;
use Saso\Presentation\Mcp\Tool\AssignItemLocationTool;
use Saso\Presentation\Mcp\Tool\DefineAttributeTool;
use Saso\Presentation\Mcp\Tool\GetItemAttributesTool;
use Saso\Presentation\Mcp\Tool\GetItemTool;
use Saso\Presentation\Mcp\Tool\GetStorageLocationTreeTool;
use Saso\Presentation\Mcp\Tool\ListAttributesTool;
use Saso\Presentation\Mcp\Tool\ListCategoriesTool;
use Saso\Presentation\Mcp\Tool\ListStorageLocationsTool;
use Saso\Presentation\Mcp\Tool\ManageCategoryTool;
use Saso\Presentation\Mcp\Tool\ManageStorageLocationTool;
use Saso\Presentation\Mcp\Tool\RegisterItemTool;
use Saso\Presentation\Mcp\Tool\SearchItemsTool;
use Saso\Presentation\Mcp\Tool\SetItemAttributeTool;
use Saso\Presentation\Mcp\Tool\SetItemStatusTool;
use Saso\Presentation\Mcp\Tool\UpdateItemTool;

/**
 * Composition root for `POST /mcp`.
 *
 * Wires the core MCP tools, JWT service, and device-token repository
 * into the McpServer, then dispatches the current request.
 *
 * ## Core tools registered
 *
 * Item read:
 *   search_items            — Keyword search over the item catalogue
 *   get_item                — Fetch a single item by ID
 *   get_item_attributes     — Fetch all typed attribute values for an item
 *
 * Item write:
 *   register_item           — Create a new item
 *   update_item             — Partially update item fields
 *   set_item_status         — Change lifecycle status (active/archived/etc.)
 *   assign_item_location    — Assign/unassign a storage location
 *   set_item_attribute      — Set a custom attribute value
 *
 * Schema (attribute definitions):
 *   list_attributes         — List all attribute definitions
 *   define_attribute        — Create or update an attribute definition
 *
 * Storage locations:
 *   list_storage_locations    — List root or child storage locations (with operational status)
 *   get_storage_location_tree — Nested tree JSON (入出庫・キープ・出庫禁止 status included)
 *   manage_storage_location   — Create, update, or delete a storage location
 *
 * Categories:
 *   list_categories — List all categories (flat or nested tree)
 *   manage_category — Create, update, or delete a classification category
 */
final class Bootstrap
{
    public static function dispatch(): void
    {
        $pdo = self::createPdo();
        $jwt = new JwtService(self::jwtSecret());

        $tokenRepo  = new PdoDeviceTokenRepository($pdo);
        $locations  = new PdoStorageLocationRepository($pdo);
        $attributes = new PdoAttributeDefinitionRepository($pdo);
        $categories = new PdoCategoryRepository($pdo);
        $search     = new NullSearchIndex();

        $registry = new InMemoryMcpToolRegistry();

        // Item — read
        $registry->registerCore(new RegistryName('search_items'), new SearchItemsTool($search));
        $registry->registerCore(new RegistryName('get_item'), new GetItemTool($pdo));
        $registry->registerCore(new RegistryName('get_item_attributes'), new GetItemAttributesTool($pdo));

        // Item — write
        $registry->registerCore(new RegistryName('register_item'), new RegisterItemTool($pdo));
        $registry->registerCore(new RegistryName('update_item'), new UpdateItemTool($pdo));
        $registry->registerCore(new RegistryName('set_item_status'), new SetItemStatusTool($pdo));
        $registry->registerCore(new RegistryName('assign_item_location'), new AssignItemLocationTool($pdo));
        $registry->registerCore(new RegistryName('set_item_attribute'), new SetItemAttributeTool($pdo));

        // Attribute schema
        $registry->registerCore(new RegistryName('list_attributes'), new ListAttributesTool($attributes));
        $registry->registerCore(new RegistryName('define_attribute'), new DefineAttributeTool($pdo));

        // Storage locations
        $registry->registerCore(new RegistryName('list_storage_locations'), new ListStorageLocationsTool($locations));
        $registry->registerCore(new RegistryName('get_storage_location_tree'), new GetStorageLocationTreeTool($pdo));
        $registry->registerCore(new RegistryName('manage_storage_location'), new ManageStorageLocationTool($pdo, $locations));

        // Categories
        $registry->registerCore(new RegistryName('list_categories'), new ListCategoriesTool($categories));
        $registry->registerCore(new RegistryName('manage_category'), new ManageCategoryTool($pdo, $categories));

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

    /**
     * Resolves the JWT signing secret (mirrors `Saso\Presentation\Api\V1\Bootstrap`).
     *
     * Boots fail closed if neither JWT_SECRET nor APP_KEY is set to a value
     * of at least 32 bytes.
     */
    private static function jwtSecret(): string
    {
        $jwtSecret = getenv('JWT_SECRET');
        if (is_string($jwtSecret) && strlen($jwtSecret) >= 32) {
            return $jwtSecret;
        }

        $appKey = getenv('APP_KEY');
        if (is_string($appKey) && strlen($appKey) >= 32) {
            return hash('sha256', $appKey, binary: true);
        }

        throw new \RuntimeException(
            'JWT_SECRET (or APP_KEY) must be set to a value of at least 32 bytes. '
            .'Refusing to boot with an insecure fallback. See .env.example.'
        );
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
