<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use Saso\Application\Verification\VerificationService;
use Saso\Domain\Mcp\McpTool;

final class CompleteVerificationSessionTool implements McpTool
{
    public function __construct(private readonly VerificationService $service)
    {
    }

    public function name(): string
    {
        return 'complete_verification_session';
    }

    public function description(): string
    {
        return 'Mark a verification session completed and return the final summary.';
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
        $sessionId = (int) ($input['sessionId'] ?? 0);
        $summary   = $this->service->complete($sessionId);

        return [
            'sessionId'              => $summary->sessionId,
            'expectedCount'          => $summary->expectedCount,
            'matchCount'             => $summary->matchCount,
            'missingCount'           => $summary->missingCount,
            'unexpectedCount'        => $summary->unexpectedCount,
            'mismatchLocationCount'  => $summary->mismatchLocationCount,
            'unknownCodeCount'       => $summary->unknownCodeCount,
            'totalEvents'            => $summary->totalEvents(),
        ];
    }

    public function requiredScope(): ?string
    {
        return 'verification:write';
    }
}
