<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use Saso\Application\Verification\VerificationService;
use Saso\Domain\Mcp\McpTool;
use Saso\Domain\Verification\VerificationMode;

final class StartVerificationSessionTool implements McpTool
{
    public function __construct(private readonly VerificationService $service)
    {
    }

    public function name(): string
    {
        return 'start_verification_session';
    }

    public function description(): string
    {
        return 'Start a stocktake or spot-verification session. Returns the session id for subsequent record_verification_scan calls.';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['mode'],
            'properties' => [
                'mode'             => ['type' => 'string', 'enum' => ['stocktake', 'spot']],
                'areaCode'         => ['type' => ['string', 'null'], 'maxLength' => 32],
                'scopeLocationId'  => ['type' => ['integer', 'null']],
            ],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $mode = VerificationMode::from((string) ($input['mode'] ?? 'stocktake'));
        $area = isset($input['areaCode']) && is_string($input['areaCode']) ? $input['areaCode'] : null;
        $scope = isset($input['scopeLocationId']) && is_int($input['scopeLocationId']) ? $input['scopeLocationId'] : null;

        $session = $this->service->start(
            mode:             $mode,
            areaCode:         $area,
            scopeLocationId:  $scope,
            startedBy:        'mcp:device:'.$deviceId,
        );

        return [
            'sessionId' => $session->id,
            'mode'      => $session->mode->value,
            'areaCode'  => $session->areaCode,
            'startedAt' => $session->startedAt->format(DATE_ATOM),
        ];
    }

    public function requiredScope(): ?string
    {
        return 'verification:write';
    }
}
