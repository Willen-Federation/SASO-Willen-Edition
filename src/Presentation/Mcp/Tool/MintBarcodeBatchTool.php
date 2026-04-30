<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use Saso\Domain\Barcode\BarcodeBatchOrigin;
use Saso\Domain\Barcode\Repository\BarcodeRepository;
use Saso\Domain\Mcp\McpTool;

/**
 * MCP tool: `mint_barcode_batch`
 *
 * Mints N pending barcode codes in a single transaction and returns the
 * batch metadata + the freshly minted codes. The caller (typically a
 * mobile app) then renders or hands off the codes to a printer.
 *
 * Scope: `barcodes:write`.
 */
final class MintBarcodeBatchTool implements McpTool
{
    public function __construct(
        private readonly BarcodeRepository $barcodes,
    ) {
    }

    public function name(): string
    {
        return 'mint_barcode_batch';
    }

    public function description(): string
    {
        return 'Pre-mint a sheet of pending barcode codes. Operators print these labels first, then attach an item to each later.';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['count'],
            'properties' => [
                'count' => [
                    'type'        => 'integer',
                    'description' => 'Number of codes to mint (1–5000).',
                    'minimum'     => 1,
                    'maximum'     => 5000,
                ],
                'sheetLayoutId' => [
                    'type'        => ['integer', 'null'],
                    'description' => 'Optional label_sheet_layout.id used for printing.',
                ],
            ],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $count   = (int) ($input['count'] ?? 0);
        $layout  = isset($input['sheetLayoutId']) && is_int($input['sheetLayoutId'])
            ? $input['sheetLayoutId']
            : null;

        $result = $this->barcodes->mintBatch(
            requestedCount:     $count,
            labelSheetLayoutId: $layout,
            createdBy:          'mcp:device:'.$deviceId,
            origin:             BarcodeBatchOrigin::Mcp,
        );

        return [
            'batchId'      => $result['batch']->id,
            'batchCode'    => $result['batch']->code,
            'createdCount' => $result['batch']->createdCount,
            'codes'        => array_map(
                static fn (\Saso\Domain\Barcode\PendingBarcode $b): string => $b->code->asString(),
                $result['codes'],
            ),
        ];
    }

    public function requiredScope(): ?string
    {
        return 'barcodes:write';
    }
}
