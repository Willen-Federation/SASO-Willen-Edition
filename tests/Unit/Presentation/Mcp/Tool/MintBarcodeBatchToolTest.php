<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Mcp\Tool;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Barcode\Repository\BarcodeRepository;
use Saso\Presentation\Mcp\Tool\MintBarcodeBatchTool;

/**
 * Argument-validation coverage for {@see MintBarcodeBatchTool}.
 *
 * Successful minting is exercised in the integration suite — these tests
 * only confirm the input guards run before the repository is touched.
 */
final class MintBarcodeBatchToolTest extends TestCase
{
    public function testNameAndScope(): void
    {
        $repo = $this->createMock(BarcodeRepository::class);
        $tool = new MintBarcodeBatchTool($repo);

        self::assertSame('mint_barcode_batch', $tool->name());
        self::assertSame('barcodes:write', $tool->requiredScope());
    }

    public function testCountBelowOneIsRejected(): void
    {
        $repo = $this->createMock(BarcodeRepository::class);
        $repo->expects(self::never())->method('mintBatch');

        $this->expectException(InvalidArgumentException::class);

        (new MintBarcodeBatchTool($repo))->invoke(['count' => 0], deviceId: 1);
    }

    public function testCountAboveMaximumIsRejected(): void
    {
        $repo = $this->createMock(BarcodeRepository::class);
        $repo->expects(self::never())->method('mintBatch');

        $this->expectException(InvalidArgumentException::class);

        (new MintBarcodeBatchTool($repo))->invoke(['count' => 5001], deviceId: 1);
    }

    public function testNonNumericSheetLayoutIdIsRejected(): void
    {
        $repo = $this->createMock(BarcodeRepository::class);
        $repo->expects(self::never())->method('mintBatch');

        $this->expectException(InvalidArgumentException::class);

        (new MintBarcodeBatchTool($repo))->invoke(
            ['count' => 1, 'sheetLayoutId' => 'abc'],
            deviceId: 1,
        );
    }

    public function testZeroSheetLayoutIdIsRejected(): void
    {
        $repo = $this->createMock(BarcodeRepository::class);
        $repo->expects(self::never())->method('mintBatch');

        $this->expectException(InvalidArgumentException::class);

        (new MintBarcodeBatchTool($repo))->invoke(
            ['count' => 1, 'sheetLayoutId' => 0],
            deviceId: 1,
        );
    }

    public function testNumericStringSheetLayoutIdIsAccepted(): void
    {
        // JSON-RPC clients sometimes serialize numeric values as strings —
        // the tool must accept "42" the same as 42 so a well-formed LLM
        // call that types the field loosely isn't silently dropped.
        $repo = $this->createMock(BarcodeRepository::class);
        $repo->expects(self::once())
            ->method('mintBatch')
            ->with(
                self::equalTo(1),
                self::equalTo(42),
                self::anything(),
                self::anything(),
            )
            ->willReturn([
                'batch' => new \Saso\Domain\Barcode\BarcodeBatch(
                    id: 1,
                    code: 'BATCH-001',
                    labelSheetLayoutId: 42,
                    requestedCount: 1,
                    createdCount: 1,
                    createdBy: 'mcp:device:1',
                    createdVia: \Saso\Domain\Barcode\BarcodeBatchOrigin::Mcp,
                    createdAt: new \DateTimeImmutable('now'),
                    updatedAt: new \DateTimeImmutable('now'),
                ),
                'codes' => [],
            ]);

        $result = (new MintBarcodeBatchTool($repo))->invoke(
            ['count' => 1, 'sheetLayoutId' => '42'],
            deviceId: 1,
        );

        self::assertSame(1, $result['createdCount']);
    }
}
