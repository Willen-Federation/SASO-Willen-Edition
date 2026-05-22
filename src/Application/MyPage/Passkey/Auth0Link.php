<?php

declare(strict_types=1);

namespace Saso\Application\MyPage\Passkey;

use Saso\Domain\Auth\AuthProviderId;

/**
 * Pair of (SASO auth_provider id, Auth0 sub) returned by
 * {@see Auth0ProviderLookup::findFor()}.
 */
final readonly class Auth0Link
{
    public function __construct(
        public AuthProviderId $providerId,
        public string $externalSubject,
    ) {
    }
}
