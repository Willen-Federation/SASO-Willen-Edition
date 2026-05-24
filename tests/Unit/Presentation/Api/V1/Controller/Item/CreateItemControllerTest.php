<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1\Controller\Item;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Saso\Application\Common\IdempotencyService;
use Saso\Application\Mobile\JwtGuard;
use Saso\Domain\MobileConnect\Jwt\JwtService;
use Saso\Domain\Shared\DomainException;
use Saso\Domain\Shared\ErrorCode;
use Saso\Presentation\Api\V1\Controller\Item\CreateItemController;
use Saso\Presentation\Api\V1\HttpRequest;

#[CoversClass(CreateItemController::class)]
final class CreateItemControllerTest extends TestCase
{
    private const SECRET = '12345678901234567890123456789012';

    private PDO $pdo;
    private CreateItemController $controller;
    private string $token;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec(
            'CREATE TABLE item (
                id                  INTEGER PRIMARY KEY AUTOINCREMENT,
                name                TEXT NOT NULL,
                category_id         INTEGER NOT NULL,
                jan_code            TEXT,
                isbn                TEXT,
                label_code          TEXT,
                note                TEXT,
                price               INTEGER NOT NULL DEFAULT 0,
                stock               INTEGER NOT NULL DEFAULT 0,
                status              TEXT,
                storage_location_id INTEGER,
                created_at          TEXT NOT NULL,
                updated_at          TEXT NOT NULL
            )',
        );
        $this->pdo->exec(
            'CREATE TABLE category (
                id      INTEGER PRIMARY KEY,
                name_ja TEXT NOT NULL
            )',
        );
        $this->pdo->exec(
            'CREATE TABLE idempotency_key (
                "key"         TEXT PRIMARY KEY,
                response_json TEXT NOT NULL,
                created_at    TEXT NOT NULL
            )',
        );
        $this->pdo->exec("INSERT INTO category (id, name_ja) VALUES (1, '本')");

        $jwt              = new JwtService(self::SECRET);
        $now              = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $this->token      = $jwt->issue(1, $now, 'tester', ['items:write'])['token'];
        $this->controller = new CreateItemController(
            $this->pdo,
            new JwtGuard($jwt),
            new IdempotencyService($this->pdo),
        );
    }

    public function testCreatesItemAndReturns201(): void
    {
        $response = $this->controller->handle($this->post(['name' => 'Widget', 'categoryId' => 1]));

        self::assertSame(201, $response->status);
        self::assertSame('Widget', $response->body['name']);
        self::assertSame('1', $response->body['categoryId']);
        self::assertSame('active', $response->body['status']);
        self::assertArrayHasKey('storageLocationId', $response->body);
        self::assertNull($response->body['storageLocationId']);
    }

    public function testRejectsEmptyName(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('name is required.');

        $this->controller->handle($this->post(['name' => '', 'categoryId' => 1]));
    }

    public function testRejectsTooLongName(): void
    {
        try {
            $this->controller->handle($this->post([
                'name'       => str_repeat('a', 256),
                'categoryId' => 1,
            ]));
            self::fail('Expected DomainException for over-long name.');
        } catch (DomainException $e) {
            self::assertSame(ErrorCode::MobileInvalidRequest, $e->errorCode());
            self::assertStringContainsString('name must be at most', $e->getMessage());
        }
    }

    public function testRejectsTooLongJanCode(): void
    {
        try {
            $this->controller->handle($this->post([
                'name'       => 'Widget',
                'categoryId' => 1,
                'janCode'    => str_repeat('1', 33),
            ]));
            self::fail('Expected DomainException for over-long janCode.');
        } catch (DomainException $e) {
            self::assertSame(ErrorCode::MobileInvalidRequest, $e->errorCode());
            self::assertStringContainsString('janCode must be at most', $e->getMessage());
        }
    }

    public function testRejectsTooLongIsbnCode(): void
    {
        try {
            $this->controller->handle($this->post([
                'name'       => 'Widget',
                'categoryId' => 1,
                'isbnCode'   => str_repeat('1', 33),
            ]));
            self::fail('Expected DomainException for over-long isbnCode.');
        } catch (DomainException $e) {
            self::assertSame(ErrorCode::MobileInvalidRequest, $e->errorCode());
            self::assertStringContainsString('isbnCode must be at most', $e->getMessage());
        }
    }

    public function testRejectsTooLongLabelCode(): void
    {
        try {
            $this->controller->handle($this->post([
                'name'       => 'Widget',
                'categoryId' => 1,
                'labelCode'  => str_repeat('x', 65),
            ]));
            self::fail('Expected DomainException for over-long labelCode.');
        } catch (DomainException $e) {
            self::assertSame(ErrorCode::MobileInvalidRequest, $e->errorCode());
            self::assertStringContainsString('labelCode must be at most', $e->getMessage());
        }
    }

    public function testRejectsInvalidCategoryId(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('categoryId must be a positive integer.');

        $this->controller->handle($this->post(['name' => 'Widget', 'categoryId' => 0]));
    }

    /** @param array<string, mixed> $body */
    private function post(array $body): HttpRequest
    {
        return new HttpRequest(
            method: 'POST',
            path: '/api/v1/items',
            headers: ['authorization' => 'Bearer '.$this->token],
            body: (string) json_encode($body),
        );
    }
}
