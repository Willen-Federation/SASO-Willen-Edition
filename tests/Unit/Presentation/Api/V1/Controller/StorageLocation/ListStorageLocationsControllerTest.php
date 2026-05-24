<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1\Controller\StorageLocation;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Saso\Application\Mobile\JwtGuard;
use Saso\Domain\MobileConnect\Exception\ScopeInsufficientException;
use Saso\Domain\MobileConnect\Jwt\JwtService;
use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;
use Saso\Infrastructure\StorageLocation\PdoStorageLocationRepository;
use Saso\Presentation\Api\V1\Controller\StorageLocation\ListStorageLocationsController;
use Saso\Presentation\Api\V1\HttpRequest;

#[CoversClass(ListStorageLocationsController::class)]
final class ListStorageLocationsControllerTest extends TestCase
{
    private const SECRET = '12345678901234567890123456789012';

    private PDO $pdo;
    private ListStorageLocationsController $controller;
    private string $token;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec(
            'CREATE TABLE storage_location (
                id                 INTEGER PRIMARY KEY,
                parent_id          INTEGER,
                code               TEXT NOT NULL UNIQUE,
                area_code          TEXT,
                name               TEXT NOT NULL,
                position           INTEGER NOT NULL DEFAULT 0,
                depth              INTEGER NOT NULL,
                location_type      TEXT NOT NULL DEFAULT \'bin\',
                description        TEXT,
                capacity           INTEGER,
                notes              TEXT,
                operational_status TEXT NOT NULL DEFAULT \'available\',
                map_image_id       INTEGER,
                map_x_ratio        REAL,
                map_y_ratio        REAL,
                created_at         TEXT NOT NULL,
                updated_at         TEXT NOT NULL
            )',
        );
        $this->pdo->exec(
            'INSERT INTO storage_location (id, parent_id, code, name, position, depth, created_at, updated_at) '.
            "VALUES (1, NULL, 'WH1', 'Warehouse 1', 0, 0, '2026-01-01 00:00:00', '2026-01-01 00:00:00')",
        );
        $this->pdo->exec(
            'INSERT INTO storage_location (id, parent_id, code, name, position, depth, created_at, updated_at) '.
            "VALUES (2, 1, 'WH1-A', 'Aisle A', 0, 1, '2026-01-01 00:00:00', '2026-01-01 00:00:00')",
        );

        $jwt              = new JwtService(self::SECRET);
        $now              = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $this->token      = $jwt->issue(1, $now, 'tester', ['items:read'])['token'];
        $this->controller = new ListStorageLocationsController(
            new PdoStorageLocationRepository($this->pdo),
            new JwtGuard($jwt),
        );
    }

    public function testListsRootsByDefault(): void
    {
        $response = $this->controller->handle($this->get([]));

        self::assertSame(200, $response->status);
        self::assertSame(1, $response->body['total']);
        self::assertSame('WH1', $response->body['data'][0]['code']);
    }

    public function testListsChildrenWhenParentIdGiven(): void
    {
        $response = $this->controller->handle($this->get(['parent_id' => '1']));

        self::assertSame(200, $response->status);
        self::assertSame(1, $response->body['total']);
        self::assertSame('WH1-A', $response->body['data'][0]['code']);
    }

    public function testRejectsZeroParentId(): void
    {
        try {
            $this->controller->handle($this->get(['parent_id' => '0']));
            self::fail('Expected DomainException');
        } catch (DomainException $e) {
            self::assertSame(ErrorCode::MobileInvalidRequest, $e->errorCode());
        }
    }

    public function testRejectsNegativeParentId(): void
    {
        try {
            $this->controller->handle($this->get(['parent_id' => '-5']));
            self::fail('Expected DomainException');
        } catch (DomainException $e) {
            self::assertSame(ErrorCode::MobileInvalidRequest, $e->errorCode());
        }
    }

    public function testRejectsNonNumericParentId(): void
    {
        try {
            $this->controller->handle($this->get(['parent_id' => 'abc']));
            self::fail('Expected DomainException');
        } catch (DomainException $e) {
            self::assertSame(ErrorCode::MobileInvalidRequest, $e->errorCode());
        }
    }

    public function testEmptyParentIdFallsBackToRoots(): void
    {
        $response = $this->controller->handle($this->get(['parent_id' => '']));

        self::assertSame(200, $response->status);
        self::assertSame(1, $response->body['total']);
        self::assertSame('WH1', $response->body['data'][0]['code']);
    }

    public function testRequiresItemsReadScope(): void
    {
        $jwt      = new JwtService(self::SECRET);
        $issuedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $token    = $jwt->issue(1, $issuedAt, 'tester', ['items:write'])['token'];

        $request = new HttpRequest(
            method: 'GET',
            path: '/api/v1/storage-locations',
            headers: ['authorization' => 'Bearer '.$token],
        );

        $this->expectException(ScopeInsufficientException::class);
        $this->controller->handle($request);
    }

    /**
     * @param array<string, string> $query
     */
    private function get(array $query): HttpRequest
    {
        return new HttpRequest(
            method: 'GET',
            path: '/api/v1/storage-locations',
            headers: ['authorization' => 'Bearer '.$this->token],
            query: $query,
        );
    }
}
