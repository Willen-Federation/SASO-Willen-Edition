<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use DateTimeImmutable;
use Saso\Domain\Barcode\BarcodeCode;
use Saso\Domain\Barcode\Repository\BarcodeRepository;
use Saso\Domain\Mcp\McpTool;

/**
 * MCP tool: `void_barcode` — terminal transition for damaged labels.
 *
 * Scope: `barcodes:write`.
 */
final class VoidBarcodeTool implements McpTool
{
    public function __construct(
        private readonly BarcodeRepository $barcodes,
    ) {
    }

    public function name(): string
    {
        return 'void_barcode';
    }

    public function description(): string
    {
        return 'Mark a pending barcode code as voided so it can never be linked to an item.';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['code', 'reason'],
            'properties' => [
                'code'   => ['type' => 'string', 'pattern' => '^PND\\d{9}$'],
                'reason' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 160],
            ],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $code   = (string) ($input['code']   ?? '');
        $reason = (string) ($input['reason'] ?? '');

        $row = $this->barcodes->findByCode(new BarcodeCode($code));
        if ($row === null) {
            return ['ok' => false, 'reason' => 'unknown_code'];
        }
        $voided = $row->void($reason, new DateTimeImmutable('now'));
        $this->barcodes->save($voided);

        return [
            'ok'          => true,
            'code'        => $voided->code->asString(),
            'status'      => $voided->status->value,
            'voidedAt'    => $voided->voidedAt?->format(DATE_ATOM),
            'voidReason'  => $voided->voidReason,
        ];
    }

    public function requiredScope(): ?string
    {
        return 'barcodes:write';
    }
}
