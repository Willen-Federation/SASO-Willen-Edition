<?php

declare(strict_types=1);

namespace Saso\Domain\Auth;

/**
 * Operator-configurable map from IdP claim names to SASO `Member` fields.
 *
 * Stored as JSON in `auth_provider.claim_mapping` (M4). The default
 * mapping mirrors the OIDC standard claim names — operators only need to
 * override entries when their IdP emits non-standard names (e.g. Azure AD
 * exposes `preferred_username` and groups under `groups`).
 *
 * The mapping is intentionally narrow: it only knows how to extract a
 * handful of fields the auto-provisioning flow (M4) requires. Provider
 * implementations preserve the full claim set on
 * {@see AuthenticatedIdentity::$claims} for any further use.
 */
final readonly class ClaimMapping
{
    public const DEFAULT_MAP = [
        'subject'      => 'sub',
        'email'        => 'email',
        'display_name' => 'name',
        'roles'        => 'groups',
    ];

    /**
     * @param array<string, string> $map SASO field name → IdP claim name
     */
    public function __construct(
        public array $map = self::DEFAULT_MAP,
    ) {
    }

    /**
     * @param array<string, string> $overrides
     */
    public static function withOverrides(array $overrides): self
    {
        return new self(array_replace(self::DEFAULT_MAP, $overrides));
    }

    /**
     * @param array<string, mixed> $claims raw IdP claim set
     */
    public function extract(string $field, array $claims): mixed
    {
        $key = $this->map[$field] ?? null;
        if ($key === null) {
            return null;
        }

        return $claims[$key] ?? null;
    }

    /**
     * @param array<string, mixed> $claims raw IdP claim set
     */
    public function extractString(string $field, array $claims): ?string
    {
        $value = $this->extract($field, $claims);

        return is_string($value) ? $value : null;
    }
}
