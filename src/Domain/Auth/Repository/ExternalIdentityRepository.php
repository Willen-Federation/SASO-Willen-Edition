<?php

declare(strict_types=1);

namespace Saso\Domain\Auth\Repository;

use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\ExternalIdentity;

/**
 * Read/write contract for `member_external_identity` rows (cf. ADR 0003).
 *
 * The pair `(authProviderId, externalSubject)` resolves to exactly one
 * member; a member is unique within a given provider — both invariants
 * are enforced by the schema.
 */
interface ExternalIdentityRepository
{
    public function find(AuthProviderId $providerId, string $externalSubject): ?ExternalIdentity;

    /**
     * @return list<ExternalIdentity>
     */
    public function listForMember(string $memberId): array;

    public function link(ExternalIdentity $identity): void;

    public function recordLogin(AuthProviderId $providerId, string $externalSubject): void;

    public function unlink(AuthProviderId $providerId, string $externalSubject): void;
}
