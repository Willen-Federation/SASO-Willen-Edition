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
use Saso\Domain\Shared\ErrorCode;
use Saso\Presentation\Api\V1\Controller\Item\UpdateItemController;
use Saso\Presentation\Api\V1\HttpRequest;

#[CoversClass(UpdateItemController::class)]
final class UpdateItemControllerTest extends TestCase
{
    private const SECRET = '12345678901234567890123456789012';

    private PDO $pdo;
    private UpdateItemController $controller;
    private string $token;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec(
            'CREATE TABLE item (
                id                  INTEGER PRIMARY KEY,
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
        $this->pdo->exec(
            "INSERT INTO item (id, name, category_id, price, stock, status, created_at, updated_at) ".
            "VALUES (1, 'テスト商品', 1, 1000, 5, 'active', '2026-01-01 00:00:00', '2026-01-01 00:00:00')",
        );

        $jwt              = new JwtService(self::SECRET);
        $now              = new DateTimeImmutable('2026-05-18 12:00:00', new DateTimeZone('UTC'));
        $this->token      = $jwt->issue(1, $now, 'tester', ['items:write'])['token'];
        $this->controller = new UpdateItemController(
            $this->pdo,
            new JwtGuard($jwt),
            new IdempotencyService($this->pdo),
        );
    }

    public function testStatusIsPersistedWhenValid(): void
    {
        $response = $this->controller->handle($this->patch('1', ['status' => 'in_storage']));

        self::assertSame(200, $response->status);
        self::assertSame('in_storage', $response->body['status']);
        self::assertSame('in_storage', $this->currentStatus(1));
    }

    public function testStatusOnlyPatchKeepsOtherColumnsUntouched(): void
    {
        $response = $this->controller->handle($this->patch('1', ['status' => 'archived']));

        self::assertSame(200, $response->status);
        $row = $this->fetchRow(1);
        self::assertSame('archived', $row['status']);
        self::assertSame('テスト商品', $row['name']);
        self::assertSame(1000, (int) $row['price']);
        self::assertSame(5, (int) $row['stock']);
    }

    public function testInvalidStatusReturns422(): void
    {
        $response = $this->controller->handle($this->patch('1', ['status' => 'unknown']));

        self::assertSame(422, $response->status);
        self::assertSame(ErrorCode::ItemInvalidStatus->value, $response->body['code']);
        self::assertSame(422, $response->body['status']);
        self::assertStringContainsString('status must be one of', (string) $response->body['detail']);
        self::assertSame('active', $this->currentStatus(1));
    }

    public function testStatusCombinedWithNamePersistsBoth(): void
    {
        $response = $this->controller->handle($this->patch('1', [
            'name'   => '新しい名前',
            'status' => 'for_sale',
        ]));

        self::assertSame(200, $response->status);
        $row = $this->fetchRow(1);
        self::assertSame('新しい名前', $row['name']);
        self::assertSame('for_sale', $row['status']);
    }

    public function testAcceptsAllNineCanonicalValues(): void
    {
        $expected = [
            'active', 'archived', 'discontinued', 'pending',
            'in_storage', 'in_use', 'for_sale', 'reserved', 'shipped',
        ];
        foreach ($expected as $value) {
            $response = $this->controller->handle($this->patch('1', ['status' => $value]));
            self::assertSame(200, $response->status, "status={$value}");
            self::assertSame($value, $this->currentStatus(1));
        }
    }

    public function testNoteIsPersistedAndReturned(): void
    {
        $response = $this->controller->handle($this->patch('1', ['note' => '入荷時要再確認']));

        self::assertSame(200, $response->status);
        self::assertSame('入荷時要再確認', $response->body['note']);
        self::assertSame('入荷時要再確認', (string) $this->fetchRow(1)['note']);
    }

    public function testNoteClearedByEmptyStringAndExplicitNull(): void
    {
        $this->pdo->exec("UPDATE item SET note = 'preset' WHERE id = 1");

        $response = $this->controller->handle($this->patch('1', ['note' => '']));
        self::assertSame(200, $response->status);
        self::assertNull($response->body['note']);
        self::assertNull($this->fetchRow(1)['note']);

        $this->pdo->exec("UPDATE item SET note = 'preset again' WHERE id = 1");

        $response = $this->controller->handle($this->patch('1', ['note' => null]));
        self::assertSame(200, $response->status);
        self::assertNull($response->body['note']);
        self::assertNull($this->fetchRow(1)['note']);
    }

    /** @param array<string, mixed> $body */
    private function patch(string $id, array $body): HttpRequest
    {
        return new HttpRequest(
            method: 'PATCH',
            path: '/api/v1/items/'.$id,
            headers: ['authorization' => 'Bearer '.$this->token],
            pathParams: ['id' => $id],
            body: (string) json_encode($body),
        );
    }

    private function currentStatus(int $id): string
    {
        return (string) $this->fetchRow($id)['status'];
    }

    /** @return array<string, mixed> */
    private function fetchRow(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM item WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        self::assertIsArray($row);

        return $row;
    }
}
