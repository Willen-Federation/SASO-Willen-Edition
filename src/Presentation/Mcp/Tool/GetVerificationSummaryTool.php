<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use Saso\Application\Verification\VerificationService;
use Saso\Domain\Mcp\McpTool;

final class GetVerificationSummaryTool implements McpTool
{
    public function __construct(private readonly VerificationService $service)
    {
    }

    public function name(): string
    {
        return 'get_verification_summary';
    }

    public function description(): string
    {
        return 'Return the running tally for a verification session (active or completed).';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['sessionId'],
            'properties' => [
                'sessionId' => ['type' => 'integer', 'minimum' => 1],
            ],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $summary = $this->service->summary((int) ($input['sessionId'] ?? 0));
        return [
            'sessionId'              => $summary->sessionId,
            'matchCount'             => $summary->matchCount,
            'missingCount'           => $summary->missingCount,
            'unexpectedCount'        => $summary->unexpectedCount,
            'mismatchLocationCount'  => $summary->mismatchLocationCount,
            'unknownCodeCount'       => $summary->unknownCodeCount,
        ];
    }

    public function requiredScope(): ?string
    {
        return 'verification:read';
    }
}
