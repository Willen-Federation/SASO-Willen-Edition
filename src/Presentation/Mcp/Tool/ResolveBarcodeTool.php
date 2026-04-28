<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use Saso\Domain\Barcode\BarcodeCode;
use Saso\Domain\Barcode\Repository\BarcodeRepository;
use Saso\Domain\Mcp\McpTool;

/**
 * MCP tool: `resolve_barcode`
 *
 * Single-scan dispatcher used by mobile clients. Given any code that the
 * scanner produced, returns:
 *   - kind=`pending`  → details of a pending pool row (status, batch, etc.)
 *   - kind=`feature`  → parsed item/colour/size (legacy 12-digit numeric)
 *   - kind=`unknown`  → nothing in either source
 *
 * Scope: `barcodes:read`.
 */
final class ResolveBarcodeTool implements McpTool
{
    public function __construct(
        private readonly BarcodeRepository $barcodes,
    ) {
    }

    public function name(): string
    {
        return 'resolve_barcode';
    }

    public function description(): string
    {
        return 'Resolve a scanned barcode to either a pending pool row or a parsed item/colour/size triple.';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['code'],
            'properties' => [
                'code' => [
                    'type'        => 'string',
                    'description' => 'Code as produced by the scanner.',
                    'minLength'   => 1,
                    'maxLength'   => 64,
                ],
            ],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $code = trim((string) ($input['code'] ?? ''));

        // Pending pool prefix.
        if (str_starts_with($code, BarcodeCode::PREFIX) && preg_match(BarcodeCode::PATTERN, $code) === 1) {
            $row = $this->barcodes->findByCode(new BarcodeCode($code));
            if ($row === null) {
                return ['kind' => 'unknown', 'code' => $code];
            }
            return [
                'kind'             => 'pending',
                'code'             => $row->code->asString(),
                'status'           => $row->status->value,
                'batchId'          => $row->batchId,
                'linkedItemId'     => $row->linkedItemId,
                'linkedAt'         => $row->linkedAt?->format(DATE_ATOM),
                'voidedAt'         => $row->voidedAt?->format(DATE_ATOM),
                'voidReason'       => $row->voidReason,
            ];
        }

        // Legacy 12-digit Feature.fullCode: dateCode(YYMD) + serial(4) + color(2) + size(2)
        if (preg_match('/^\d{12}$/', $code) === 1) {
            return [
                'kind'      => 'feature',
                'code'      => $code,
                'itemId'    => substr($code, 0, 8),
                'colorCode' => substr($code, 8, 2),
                'sizeCode'  => substr($code, 10, 2),
            ];
        }

        return ['kind' => 'unknown', 'code' => $code];
    }

    public function requiredScope(): ?string
    {
        return 'barcodes:read';
    }
}
