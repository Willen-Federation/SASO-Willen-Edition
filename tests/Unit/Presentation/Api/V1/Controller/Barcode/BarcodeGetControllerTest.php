<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1\Controller\Barcode;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Saso\Domain\Barcode\BarcodeBatch;
use Saso\Domain\Barcode\BarcodeBatchOrigin;
use Saso\Domain\Barcode\BarcodeCode;
use Saso\Domain\Barcode\BarcodeStatus;
use Saso\Domain\Barcode\PendingBarcode;
use Saso\Domain\Barcode\Repository\BarcodeRepository;
use Saso\Presentation\Api\V1\Controller\Barcode\BarcodeGetController;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

final class BarcodeGetControllerTest extends TestCase
{
    public function testReturns404OnMalformedCode(): void
    {
        $controller = new BarcodeGetController($this->fakeRepo(null));

        $response = $controller->handle(new HttpRequest(
            method: 'GET',
            path: '/api/v1/barcode/!!!',
            pathParams: ['code' => '!!!'],
        ));

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(404, $response->status);
        self::assertSame('SASO-BARCODE-4001', $response->body['code']);
        // Detail must not echo user input back — that prevents reflection abuse
        // and avoids leaking malformed values into logs verbatim.
        self::assertStringNotContainsString('!!!', (string) $response->body['detail']);
    }

    public function testReturns404WhenCodeNotInPool(): void
    {
        $controller = new BarcodeGetController($this->fakeRepo(null));

        $response = $controller->handle(new HttpRequest(
            method: 'GET',
            path: '/api/v1/barcode/PND000000001',
            pathParams: ['code' => 'PND000000001'],
        ));

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(404, $response->status);
        self::assertSame('SASO-BARCODE-4004', $response->body['code']);
    }

    public function testReturns200WithNullItemForUnlinkedBarcode(): void
    {
        $pending = new PendingBarcode(
            id: 1,
            code: new BarcodeCode('PND000000042'),
            status: BarcodeStatus::Pending,
            batchId: 1,
            linkedItemId: null,
            linkedAt: null,
            linkedByDeviceId: null,
            voidedAt: null,
            voidReason: null,
            createdAt: new DateTimeImmutable('2026-05-24T10:00:00Z'),
        );
        $controller = new BarcodeGetController($this->fakeRepo($pending));

        $response = $controller->handle(new HttpRequest(
            method: 'GET',
            path: '/api/v1/barcode/PND000000042',
            pathParams: ['code' => 'PND000000042'],
        ));

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->status);
        self::assertSame('PND000000042', $response->body['code']);
        self::assertSame('pending', $response->body['status']);
        self::assertNull($response->body['item']);
    }

    public function testReturns200ForVoidedBarcodeWithoutLinkedItemLookup(): void
    {
        $voided = new PendingBarcode(
            id: 2,
            code: new BarcodeCode('PND000000099'),
            status: BarcodeStatus::Voided,
            batchId: 1,
            linkedItemId: null,
            linkedAt: null,
            linkedByDeviceId: null,
            voidedAt: new DateTimeImmutable('2026-05-24T11:00:00Z'),
            voidReason: 'damaged',
            createdAt: new DateTimeImmutable('2026-05-24T10:00:00Z'),
        );
        $controller = new BarcodeGetController($this->fakeRepo($voided));

        $response = $controller->handle(new HttpRequest(
            method: 'GET',
            path: '/api/v1/barcode/PND000000099',
            pathParams: ['code' => 'PND000000099'],
        ));

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->status);
        self::assertSame('voided', $response->body['status']);
        self::assertNull($response->body['item']);
    }

    public function testUppercasesIncomingCode(): void
    {
        $pending = new PendingBarcode(
            id: 3,
            code: new BarcodeCode('BC00007'),
            status: BarcodeStatus::Pending,
            batchId: 1,
            linkedItemId: null,
            linkedAt: null,
            linkedByDeviceId: null,
            voidedAt: null,
            voidReason: null,
            createdAt: new DateTimeImmutable('2026-05-24T10:00:00Z'),
        );

        $repo = new class ($pending) implements BarcodeRepository {
            public ?BarcodeCode $observed = null;

            public function __construct(private readonly ?PendingBarcode $row)
            {
            }

            public function findByCode(BarcodeCode $code): ?PendingBarcode
            {
                $this->observed = $code;
                return $this->row;
            }

            public function findBatchById(int $batchId): ?BarcodeBatch
            {
                return null;
            }

            public function mintBatch(
                int $requestedCount,
                ?int $labelSheetLayoutId,
                ?string $createdBy,
                BarcodeBatchOrigin $origin,
                ?string $prefix = null,
                ?int $startNo = null,
                ?string $codeType = null,
            ): array {
                throw new RuntimeException('mintBatch not used in this test');
            }

            public function save(PendingBarcode $barcode): void
            {
            }

            public function listByStatus(BarcodeStatus $status, int $limit = 100, int $offset = 0): array
            {
                return [];
            }
        };

        $controller = new BarcodeGetController($repo);

        $response = $controller->handle(new HttpRequest(
            method: 'GET',
            path: '/api/v1/barcode/bc00007',
            pathParams: ['code' => 'bc00007'],
        ));

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->status);
        self::assertSame('BC00007', $repo->observed?->asString());
    }

    private function fakeRepo(?PendingBarcode $row): BarcodeRepository
    {
        return new class ($row) implements BarcodeRepository {
            public function __construct(private readonly ?PendingBarcode $row)
            {
            }

            public function findByCode(BarcodeCode $code): ?PendingBarcode
            {
                return $this->row;
            }

            public function findBatchById(int $batchId): ?BarcodeBatch
            {
                return null;
            }

            public function mintBatch(
                int $requestedCount,
                ?int $labelSheetLayoutId,
                ?string $createdBy,
                BarcodeBatchOrigin $origin,
                ?string $prefix = null,
                ?int $startNo = null,
                ?string $codeType = null,
            ): array {
                throw new RuntimeException('mintBatch not used in this test');
            }

            public function save(PendingBarcode $barcode): void
            {
            }

            public function listByStatus(BarcodeStatus $status, int $limit = 100, int $offset = 0): array
            {
                return [];
            }
        };
    }
}
