<?php

declare(strict_types=1);

namespace Saso\Domain\Auth;

use InvalidArgumentException;

/**
 * Result of a successful login from an {@see AuthProvider}.
 *
 * The Application layer takes this and either:
 *
 *   1. Looks up an existing `Member` by `(authProviderId, externalSubject)`
 *      and signs them in.
 *   2. If no member exists, runs first-login provisioning (M4) using
 *      `email`, `displayName`, and any `claims` named in the provider's
 *      claim mapping.
 *
 * The provider has already verified the credentials / signature / nonce /
 * state by the time this object is constructed; consumers may treat the
 * fields as authoritative.
 */
final readonly class AuthenticatedIdentity
{
    /**
     * @param array<string, mixed> $claims raw IdP claims preserved for
     *                                     downstream provisioning logic
     */
    public function __construct(
        public AuthProviderId $authProviderId,
        public string $externalSubject,
        public string $email,
        public string $displayName,
        public array $claims = [],
    ) {
        if ($externalSubject === '') {
            throw new InvalidArgumentException(
                'AuthenticatedIdentity.externalSubject must not be empty — IdPs are required to issue a stable subject.',
            );
        }
    }
}
