<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use Saso\Domain\Barcode\BarcodeStatus;
use Saso\Domain\Barcode\Repository\BarcodeRepository;
use Saso\Domain\Mcp\McpTool;

/**
 * MCP tool: `list_pending_barcodes` — paginated list of un-linked codes.
 *
 * Scope: `barcodes:read`.
 */
final class ListPendingBarcodesTool implements McpTool
{
    public function __construct(
        private readonly BarcodeRepository $barcodes,
    ) {
    }

    public function name(): string
    {
        return 'list_pending_barcodes';
    }

    public function description(): string
    {
        return 'List pending (un-linked) pre-minted barcode codes, most recent first.';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'limit'  => ['type' => 'integer', 'minimum' => 1, 'maximum' => 200, 'default' => 50],
                'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0],
            ],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $limit  = isset($input['limit']) ? min(200, max(1, (int) $input['limit'])) : 50;
        $offset = isset($input['offset']) ? max(0, (int) $input['offset']) : 0;

        $rows = $this->barcodes->listByStatus(BarcodeStatus::Pending, $limit, $offset);

        return [
            'items' => array_map(
                static fn (\Saso\Domain\Barcode\PendingBarcode $b): array => [
                    'code'      => $b->code->asString(),
                    'batchId'   => $b->batchId,
                    'createdAt' => $b->createdAt->format(DATE_ATOM),
                ],
                $rows,
            ),
            'limit'  => $limit,
            'offset' => $offset,
        ];
    }

    public function requiredScope(): ?string
    {
        return 'barcodes:read';
    }
}
