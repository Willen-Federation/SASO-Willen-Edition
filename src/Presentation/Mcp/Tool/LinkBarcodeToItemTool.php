<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use DateTimeImmutable;
use Saso\Domain\Barcode\BarcodeCode;
use Saso\Domain\Barcode\Repository\BarcodeRepository;
use Saso\Domain\Mcp\McpTool;

/**
 * MCP tool: `link_barcode_to_item` — transitions a pending pool row to
 * `linked` and records the item id + scanning device.
 *
 * Scope: `barcodes:write`.
 */
final class LinkBarcodeToItemTool implements McpTool
{
    public function __construct(
        private readonly BarcodeRepository $barcodes,
    ) {
    }

    public function name(): string
    {
        return 'link_barcode_to_item';
    }

    public function description(): string
    {
        return 'Attach a pending barcode code to an item id. Pending → Linked transition.';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['code', 'itemId'],
            'properties' => [
                'code'   => ['type' => 'string', 'pattern' => '^PND\\d{9}$'],
                'itemId' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 64],
            ],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $code   = (string) ($input['code']   ?? '');
        $itemId = (string) ($input['itemId'] ?? '');

        $row = $this->barcodes->findByCode(new BarcodeCode($code));
        if ($row === null) {
            return ['ok' => false, 'reason' => 'unknown_code'];
        }
        $linked = $row->link($itemId, new DateTimeImmutable('now'), $deviceId);
        $this->barcodes->save($linked);

        return [
            'ok'          => true,
            'code'        => $linked->code->asString(),
            'status'      => $linked->status->value,
            'itemId'      => $linked->linkedItemId,
            'linkedAt'    => $linked->linkedAt?->format(DATE_ATOM),
        ];
    }

    public function requiredScope(): ?string
    {
        return 'barcodes:write';
    }
}
