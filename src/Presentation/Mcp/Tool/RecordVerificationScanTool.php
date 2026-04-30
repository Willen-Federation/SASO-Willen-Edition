<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use Saso\Application\Verification\VerificationService;
use Saso\Domain\Mcp\McpTool;
use Saso\Domain\Verification\ResolvedKind;

final class RecordVerificationScanTool implements McpTool
{
    public function __construct(private readonly VerificationService $service)
    {
    }

    public function name(): string
    {
        return 'record_verification_scan';
    }

    public function description(): string
    {
        return 'Record one scan against an active verification session. Returns the resulting event with its classification (match / missing / unexpected / mismatch_location / unknown_code).';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['sessionId', 'scannedCode', 'resolvedKind'],
            'properties' => [
                'sessionId'          => ['type' => 'integer', 'minimum' => 1],
                'scannedCode'        => ['type' => 'string', 'minLength' => 1, 'maxLength' => 64],
                'resolvedKind'       => ['type' => 'string', 'enum' => ['pending', 'feature', 'unknown']],
                'resolvedItemId'     => ['type' => ['string', 'null']],
                'expectedLocationId' => ['type' => ['integer', 'null']],
                'actualLocationId'   => ['type' => ['integer', 'null']],
            ],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $event = $this->service->recordScan(
            sessionId:          (int) ($input['sessionId'] ?? 0),
            scannedCode:        (string) ($input['scannedCode'] ?? ''),
            resolvedKind:       ResolvedKind::from((string) ($input['resolvedKind'] ?? 'unknown')),
            resolvedItemId:     isset($input['resolvedItemId'])     && is_string($input['resolvedItemId']) ? $input['resolvedItemId'] : null,
            expectedLocationId: isset($input['expectedLocationId']) && is_int($input['expectedLocationId']) ? $input['expectedLocationId'] : null,
            actualLocationId:   isset($input['actualLocationId'])   && is_int($input['actualLocationId']) ? $input['actualLocationId'] : null,
            deviceId:           $deviceId,
        );

        return [
            'eventId'   => $event->id,
            'sessionId' => $event->sessionId,
            'result'    => $event->result->value,
        ];
    }

    public function requiredScope(): ?string
    {
        return 'verification:write';
    }
}
