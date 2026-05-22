<?php

declare(strict_types=1);

namespace Saso\Application\MyPage\Passkey;

use PDO;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\AuthProviderType;

/**
 * Resolves the Auth0 identity linked to a given SASO member.
 *
 * Used in two places:
 *   1. The My Page passkey card: we need the user's Auth0 `sub` to ask the
 *      Management API for their passkeys.
 *   2. `PasskeyBeginDIContainer`: we need the SASO `auth_provider.id` of
 *      the Auth0 row so we can hand it to `LoginOrchestrator::beginLogin`.
 *
 * Returns `null` when the member is local-only (signed in with a
 * username + password) or is linked to a non-Auth0 provider — both cases
 * are normal and the caller renders a "Passkeys require Auth0" banner.
 */
final class Auth0ProviderLookup
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * `claim_mapping._config.flavor === 'auth0'` is the canonical marker
     * for an Auth0 row (see {@see \Saso\Infrastructure\Auth\AuthProviderFactory}).
     * Rows persisted before that marker existed are detected by the
     * `*.auth0.com` issuer URL — same rescue heuristic the factory uses.
     */
    public function findFor(string $memberId): ?Auth0Link
    {
        if ($memberId === '') {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT p.id AS provider_id,
                        p.issuer_or_metadata_url,
                        p.claim_mapping,
                        e.external_subject
                   FROM member_external_identity e
                   INNER JOIN auth_provider p ON p.id = e.auth_provider_id
                  WHERE e.member_id = :id
                    AND p.type = :type
                    AND p.enabled = 1
                  ORDER BY e.last_login_at DESC, e.created_at DESC',
            );
            $stmt->bindValue(':id', $memberId);
            $stmt->bindValue(':type', AuthProviderType::Oidc->value);
            $stmt->execute();
        } catch (\Throwable) {
            return null;
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!is_array($rows)) {
            return null;
        }

        foreach ($rows as $row) {
            if (!$this->looksLikeAuth0($row)) {
                continue;
            }
            $subject = (string) ($row['external_subject'] ?? '');
            if ($subject === '') {
                continue;
            }
            return new Auth0Link(
                providerId: new AuthProviderId((int) $row['provider_id']),
                externalSubject: $subject,
            );
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function looksLikeAuth0(array $row): bool
    {
        $mapping = $row['claim_mapping'] ?? null;
        if (is_string($mapping) && $mapping !== '') {
            $decoded = json_decode($mapping, true);
            if (is_array($decoded)) {
                $flavor = $decoded['_config']['flavor'] ?? null;
                if (is_string($flavor) && strtolower($flavor) === 'auth0') {
                    return true;
                }
            }
        }

        $issuer = $row['issuer_or_metadata_url'] ?? null;
        if (is_string($issuer) && $issuer !== '') {
            $host = parse_url($issuer, PHP_URL_HOST);
            if (is_string($host) && preg_match('/(^|\.)auth0\.com$/i', $host) === 1) {
                return true;
            }
        }

        return false;
    }
}
