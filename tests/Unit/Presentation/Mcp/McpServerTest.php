<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Mcp;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Mcp\McpTool;
use Saso\Domain\MobileConnect\DeviceToken;
use Saso\Domain\MobileConnect\Jwt\JwtService;
use Saso\Domain\Plugin\Registry\RegistryName;
use Saso\Infrastructure\MobileConnect\PdoDeviceTokenRepository;
use Saso\Infrastructure\Plugin\Registry\InMemoryMcpToolRegistry;
use Saso\Presentation\Mcp\McpServer;

final class McpServerTest extends TestCase
{
    private const SECRET = '12345678901234567890123456789012';

    private JwtService $jwt;
    private PDO $pdo;
    private PdoDeviceTokenRepository $tokenRepo;
    private InMemoryMcpToolRegistry $registry;

    protected function setUp(): void
    {
        $this->jwt = new JwtService(self::SECRET);

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(
            'CREATE TABLE device_token (
                id                 INTEGER PRIMARY KEY,
                token_hash         TEXT NOT NULL UNIQUE,
                refresh_token_hash TEXT,
                member_id          TEXT,
                scopes             TEXT,
                device_name        TEXT NOT NULL,
                revoked            INTEGER NOT NULL DEFAULT 0,
                last_used_at       TEXT,
                expires_at         TEXT NOT NULL,
                created_at         TEXT NOT NULL
            )',
        );
        $this->tokenRepo = new PdoDeviceTokenRepository($this->pdo, new DateTimeZone('UTC'));
        $this->registry  = new InMemoryMcpToolRegistry();
    }

    private function makeServer(): McpServer
    {
        return new McpServer($this->registry, $this->jwt, $this->tokenRepo);
    }

    /**
     * @param list<string> $scopes
     */
    private function seedDevice(int $id = 1, bool $revoked = false, array $scopes = []): string
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $token = new DeviceToken(
            id: $id,
            tokenHash: str_repeat('a', 64),
            refreshTokenHash: null,
            deviceName: 'Test Device',
            revoked: $revoked,
            lastUsedAt: null,
            expiresAt: $now->modify('+1 year'),
            createdAt: $now,
            memberId: 'admin_test',
            scopes: $scopes,
        );
        $this->tokenRepo->save($token);

        $result = $this->jwt->issue($id, $now, 'admin_test', $scopes);

        return $result['token'];
    }

    public function testParseErrorOnInvalidJson(): void
    {
        $server   = $this->makeServer();
        $response = $server->handle([], 'not-json');

        self::assertSame(400, $response->httpStatus);
        self::assertSame(-32700, $response->toArray()['error']['code']);
    }

    public function testInvalidRequestOnMissingMethod(): void
    {
        $server   = $this->makeServer();
        $response = $server->handle([], '{"jsonrpc":"2.0","id":1}');

        self::assertSame(-32600, $response->toArray()['error']['code']);
    }

    public function testInitializeRequiresNoAuth(): void
    {
        $server   = $this->makeServer();
        $body     = json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => []]);
        $response = $server->handle([], (string) $body);

        $arr = $response->toArray();
        self::assertArrayHasKey('result', $arr);
        self::assertArrayHasKey('serverInfo', $arr['result']);
        self::assertSame('SASO MCP Server', $arr['result']['serverInfo']['name']);
    }

    public function testToolsListRequiresAuth(): void
    {
        $server   = $this->makeServer();
        $body     = json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list', 'params' => []]);
        $response = $server->handle([], (string) $body);

        self::assertSame(401, $response->httpStatus);
    }

    public function testToolsListWithValidAuth(): void
    {
        $jwt = $this->seedDevice();
        $this->registry->registerCore(new RegistryName('echo_tool'), $this->makeFakeTool());

        $server   = $this->makeServer();
        $body     = json_encode(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list', 'params' => []]);
        $response = $server->handle(['authorization' => 'Bearer '.$jwt], (string) $body);

        $arr = $response->toArray();
        self::assertArrayHasKey('result', $arr);
        self::assertCount(1, $arr['result']['tools']);
        self::assertSame('echo_tool', $arr['result']['tools'][0]['name']);
    }

    public function testToolsCallDispatchesToTool(): void
    {
        $jwt = $this->seedDevice();
        $this->registry->registerCore(new RegistryName('echo_tool'), $this->makeFakeTool());

        $server = $this->makeServer();
        $body   = json_encode([
            'jsonrpc' => '2.0',
            'id'      => 3,
            'method'  => 'tools/call',
            'params'  => ['name' => 'echo_tool', 'arguments' => ['msg' => 'hello']],
        ]);
        $response = $server->handle(['authorization' => 'Bearer '.$jwt], (string) $body);

        $arr = $response->toArray();
        self::assertArrayHasKey('result', $arr);
        self::assertNotEmpty($arr['result']['content']);
        $text = json_decode($arr['result']['content'][0]['text'], true);
        self::assertSame('hello', $text['echo']);
    }

    public function testToolsCallReturnsToolNotFoundForUnknownTool(): void
    {
        $jwt = $this->seedDevice();

        $server = $this->makeServer();
        $body   = json_encode([
            'jsonrpc' => '2.0',
            'id'      => 4,
            'method'  => 'tools/call',
            'params'  => ['name' => 'no_such_tool', 'arguments' => []],
        ]);
        $response = $server->handle(['authorization' => 'Bearer '.$jwt], (string) $body);

        self::assertSame(-32002, $response->toArray()['error']['code']);
    }

    public function testToolsCallUnauthorizedForRevokedToken(): void
    {
        $jwt = $this->seedDevice(revoked: true);

        $server = $this->makeServer();
        $body   = json_encode([
            'jsonrpc' => '2.0',
            'id'      => 5,
            'method'  => 'tools/call',
            'params'  => ['name' => 'echo_tool', 'arguments' => []],
        ]);
        $response = $server->handle(['authorization' => 'Bearer '.$jwt], (string) $body);

        self::assertSame(401, $response->httpStatus);
    }

    public function testMethodNotFoundForUnknownMethod(): void
    {
        $server   = $this->makeServer();
        $body     = json_encode(['jsonrpc' => '2.0', 'id' => 6, 'method' => 'unknown/method']);
        $response = $server->handle([], (string) $body);

        self::assertSame(-32601, $response->toArray()['error']['code']);
    }

    public function testToolsCallReturnsScopeInsufficientWhenScopeMissing(): void
    {
        $jwt = $this->seedDevice(scopes: ['items:read']);
        $this->registry->registerCore(new RegistryName('write_tool'), $this->makeScopedTool('items:write'));

        $server = $this->makeServer();
        $body   = json_encode([
            'jsonrpc' => '2.0',
            'id'      => 7,
            'method'  => 'tools/call',
            'params'  => ['name' => 'write_tool', 'arguments' => []],
        ]);
        $response = $server->handle(['authorization' => 'Bearer '.$jwt], (string) $body);

        self::assertSame(403, $response->httpStatus);
        $arr = $response->toArray();
        self::assertSame(-32003, $arr['error']['code']);
        self::assertSame('items:write', $arr['error']['data']['requiredScope']);
    }

    public function testToolsCallAllowedWhenScopeGranted(): void
    {
        $jwt = $this->seedDevice(scopes: ['items:write']);
        $this->registry->registerCore(new RegistryName('write_tool'), $this->makeScopedTool('items:write'));

        $server = $this->makeServer();
        $body   = json_encode([
            'jsonrpc' => '2.0',
            'id'      => 8,
            'method'  => 'tools/call',
            'params'  => ['name' => 'write_tool', 'arguments' => []],
        ]);
        $response = $server->handle(['authorization' => 'Bearer '.$jwt], (string) $body);

        self::assertSame(200, $response->httpStatus);
        $arr = $response->toArray();
        self::assertArrayHasKey('result', $arr);
    }

    private function makeScopedTool(string $scope): McpTool
    {
        return new class ($scope) implements McpTool {
            public function __construct(private readonly string $scope)
            {
            }

            public function name(): string
            {
                return 'write_tool';
            }

            public function description(): string
            {
                return 'Scoped test tool.';
            }

            public function inputSchema(): array
            {
                return ['type' => 'object'];
            }

            public function invoke(array $input, int $deviceId): array
            {
                return ['ok' => true];
            }

            public function requiredScope(): ?string
            {
                return $this->scope;
            }
        };
    }

    private function makeFakeTool(): McpTool
    {
        return new class () implements McpTool {
            public function name(): string
            {
                return 'echo_tool';
            }

            public function description(): string
            {
                return 'Echoes input.';
            }

            public function inputSchema(): array
            {
                return ['type' => 'object', 'properties' => ['msg' => ['type' => 'string']]];
            }

            public function invoke(array $input, int $deviceId): array
            {
                return ['echo' => $input['msg'] ?? ''];
            }

            public function requiredScope(): ?string
            {
                return null;
            }
        };
    }
}
