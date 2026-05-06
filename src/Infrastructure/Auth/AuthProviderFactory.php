<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Auth;

use PDO;
use Saso\Domain\Auth\AuthProvider;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\AuthProviderRecord;
use Saso\Domain\Auth\AuthProviderType;
use Saso\Domain\Auth\Exception\ProviderMisconfiguredException;
use Saso\Domain\Auth\Repository\AuthProviderRepository;
use Saso\Infrastructure\Auth\Provider\Auth0Provider;
use Saso\Infrastructure\Auth\Provider\CognitoProvider;
use Saso\Infrastructure\Auth\Provider\FirebaseProvider;
use Saso\Infrastructure\Auth\Provider\GenericOidcProvider;
use Saso\Infrastructure\Auth\Provider\LocalProvider;
use Saso\Infrastructure\Auth\Provider\SamlProvider;

/**
 * Factory that resolves a {@see AuthProviderRecord} into the right concrete
 * {@see AuthProvider} class.
 *
 * Dispatch order:
 *   1. `type=local`              → {@see LocalProvider}
 *   2. `type=saml`               → {@see SamlProvider}
 *   3. `type=oidc`, then on `_config.flavor`:
 *        - `auth0`     → {@see Auth0Provider}
 *        - `cognito`   → {@see CognitoProvider}
 *        - `firebase`  → {@see FirebaseProvider}
 *        - `oidc` / null → {@see GenericOidcProvider}
 *
 * Concrete providers are constructed lazily and cached for the lifetime of
 * this factory instance — every Application request typically resolves
 * exactly one provider, so the cache is mostly a defensive measure.
 */
final class AuthProviderFactory
{
    /** @var array<int, AuthProvider> id.value → provider */
    private array $cache = [];

    public function __construct(
        private readonly AuthProviderRepository $repository,
        private readonly PDO $pdo,
        private readonly string $baseUrl,
    ) {
    }

    public function forId(AuthProviderId $id): AuthProvider
    {
        if (isset($this->cache[$id->value])) {
            return $this->cache[$id->value];
        }
        $record = $this->repository->findById($id);
        if ($record === null) {
            throw ProviderMisconfiguredException::for(
                $id->asString(),
                'No auth_provider row matches this id.',
            );
        }
        return $this->cache[$id->value] = $this->build($record);
    }

    public function forRecord(AuthProviderRecord $record): AuthProvider
    {
        if (isset($this->cache[$record->id->value])) {
            return $this->cache[$record->id->value];
        }
        return $this->cache[$record->id->value] = $this->build($record);
    }

    private function build(AuthProviderRecord $record): AuthProvider
    {
        return match ($record->type) {
            AuthProviderType::Local => new LocalProvider($record, $this->pdo),
            AuthProviderType::Saml  => new SamlProvider(
                $record,
                $this->makeAcsUrl($record->id),
                $this->makeSlsUrl($record->id),
            ),
            AuthProviderType::Oidc  => $this->buildOidc($record),
            AuthProviderType::WebAuthn => throw ProviderMisconfiguredException::for(
                $record->name,
                'WebAuthn is handled by the passkey endpoints, not by the redirect factory.',
            ),
        };
    }

    private function buildOidc(AuthProviderRecord $record): AuthProvider
    {
        $cfg     = $record->claimMapping['_config'] ?? [];
        $flavor  = is_array($cfg) && isset($cfg['flavor']) && is_string($cfg['flavor'])
            ? strtolower($cfg['flavor'])
            : 'oidc';

        // Rescue heuristic for rows saved by the pre-fix wizard, which
        // persisted claim_mapping = NULL and therefore lost the flavor
        // discriminator. Without this any *.auth0.com row would silently
        // downgrade to GenericOidcProvider and surface SASO-AUTH-1006 at
        // login time. Only kicks in when no flavor is stored, so explicit
        // operator choices always win.
        if ($flavor === 'oidc' && is_string($record->issuerOrMetadataUrl)) {
            $host = parse_url($record->issuerOrMetadataUrl, PHP_URL_HOST);
            if (is_string($host) && preg_match('/(^|\.)auth0\.com$/i', $host) === 1) {
                $flavor = 'auth0';
            }
        }

        $callback = $this->makeCallbackUrl($record->id);

        return match ($flavor) {
            'auth0'    => new Auth0Provider($record, $callback),
            'cognito'  => new CognitoProvider($record, $callback),
            'firebase' => new FirebaseProvider($record, $callback),
            default    => new GenericOidcProvider($record, $callback),
        };
    }

    private function makeCallbackUrl(AuthProviderId $id): string
    {
        return rtrim($this->baseUrl, '/').'/auth/callback';
    }

    private function makeAcsUrl(AuthProviderId $id): string
    {
        return rtrim($this->baseUrl, '/').'/auth/saml/acs';
    }

    private function makeSlsUrl(AuthProviderId $id): string
    {
        return rtrim($this->baseUrl, '/').'/auth/saml/sls';
    }
}
