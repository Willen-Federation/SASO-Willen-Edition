<?php

declare(strict_types=1);

namespace Saso\Domain\Auth;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Immutable view of one `member_external_identity` row.
 *
 * `memberId` is the legacy `Member.id` (until M4-G physically migrates
 * the bounded context); `authProviderId` references `auth_provider.id`;
 * `externalSubject` is the IdP-issued subject (OIDC `sub` / SAML
 * `NameID`).
 */
final readonly class ExternalIdentity
{
    public function __construct(
        public int $memberId,
        public AuthProviderId $authProviderId,
        public string $externalSubject,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?DateTimeImmutable $lastLoginAt,
    ) {
        if ($memberId < 1) {
            throw new InvalidArgumentException('ExternalIdentity.memberId must be a positive integer.');
        }
        if ($externalSubject === '') {
            throw new InvalidArgumentException('ExternalIdentity.externalSubject must not be empty.');
        }
    }
}
