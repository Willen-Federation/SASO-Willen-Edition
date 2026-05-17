<?php

declare(strict_types=1);

namespace Saso\Domain\MobileConnect\Jwt;

/**
 * Verified JWT claims for an authenticated mobile/MCP request.
 *
 * Returned by {@see JwtService::verify()} after signature + expiry checks
 * pass. Carries the device token row ID (`deviceId`), the issuing admin's
 * member ID (`memberId` — null only for legacy tokens minted before the
 * mobile-pairing hardening), and the OAuth2-style scopes granted at
 * issuance time (`scopes`).
 */
final readonly class JwtClaims
{
    /**
     * @param list<string> $scopes
     */
    public function __construct(
        public int $deviceId,
        public ?string $memberId,
        public array $scopes,
    ) {
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }
}
