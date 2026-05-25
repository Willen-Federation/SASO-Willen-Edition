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

    /**
     * Transfer an existing external identity to a different member.
     *
     * Used when the signed-in user proves ownership of an external account
     * that is currently attached to a different member (e.g. a JIT-provisioned
     * account created on a previous IdP sign-in). The caller must have already
     * verified IdP-side ownership before invoking this method.
     */
    public function relink(AuthProviderId $providerId, string $externalSubject, string $newMemberId): void;

    public function recordLogin(AuthProviderId $providerId, string $externalSubject): void;

    public function unlink(AuthProviderId $providerId, string $externalSubject): void;
}
