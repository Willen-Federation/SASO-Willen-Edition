<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1\Controller\Item;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Saso\Application\Mobile\JwtGuard;
use Saso\Domain\MobileConnect\Jwt\JwtService;
use Saso\Presentation\Api\V1\Controller\Item\ListItemsController;
use Saso\Presentation\Api\V1\HttpRequest;

#[CoversClass(ListItemsController::class)]
final class ListItemsControllerTest extends TestCase
{
    private const SECRET = '12345678901234567890123456789012';

    private PDO $pdo;
    private ListItemsController $controller;
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
        $this->pdo->exec("INSERT INTO category (id, name_ja) VALUES (1, '本')");
        $this->pdo->exec(
            'INSERT INTO item (id, name, category_id, status, created_at, updated_at) VALUES '.
            "(1, 'Widget A', 1, 'active', '2026-05-01 00:00:00', '2026-05-01 00:00:00'),".
            "(2, 'Gadget B', 1, 'active', '2026-05-01 00:00:00', '2026-05-01 00:00:00'),".
            "(3, '50% off SALE', 1, 'active', '2026-05-01 00:00:00', '2026-05-01 00:00:00'),".
            "(4, 'under_score sample', 1, 'active', '2026-05-01 00:00:00', '2026-05-01 00:00:00')",
        );

        $jwt              = new JwtService(self::SECRET);
        $now              = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $this->token      = $jwt->issue(1, $now, 'tester', ['items:read'])['token'];
        $this->controller = new ListItemsController(
            $this->pdo,
            new JwtGuard($jwt),
        );
    }

    public function testKeywordPercentDoesNotMatchEveryRow(): void
    {
        $response = $this->controller->handle($this->get(['q' => '%']));

        self::assertSame(200, $response->status);
        // Only the literal "50% off SALE" row contains a percent sign.
        $names = array_map(static fn (array $r): string => (string) $r['name'], $response->body['data']);
        self::assertCount(1, $names, sprintf('Got rows: %s', implode(', ', $names)));
        self::assertSame('50% off SALE', $names[0]);
    }

    public function testKeywordUnderscoreOnlyMatchesLiteralUnderscore(): void
    {
        $response = $this->controller->handle($this->get(['q' => '_']));

        self::assertSame(200, $response->status);
        $names = array_map(static fn (array $r): string => (string) $r['name'], $response->body['data']);
        self::assertCount(1, $names, sprintf('Got rows: %s', implode(', ', $names)));
        self::assertSame('under_score sample', $names[0]);
    }

    public function testKeywordPlainSubstringMatches(): void
    {
        $response = $this->controller->handle($this->get(['q' => 'widget']));

        self::assertSame(200, $response->status);
        $names = array_map(static fn (array $r): string => (string) $r['name'], $response->body['data']);
        self::assertSame(['Widget A'], $names);
    }

    /** @param array<string, string> $query */
    private function get(array $query): HttpRequest
    {
        return new HttpRequest(
            method: 'GET',
            path: '/api/v1/items',
            headers: ['authorization' => 'Bearer '.$this->token],
            query: $query,
        );
    }
}
