<?php

namespace saso\mypage;

use GuzzleHttp\Client;
use Saso\Application\MyPage\Passkey\Auth0ManagementApiException;
use Saso\Application\MyPage\Passkey\Auth0PasskeyConfig;
use Saso\Application\MyPage\Passkey\Auth0PasskeyService;
use Saso\Application\MyPage\Passkey\Auth0ProviderLookup;
use Saso\Domain\MobileConnect\DeviceToken;
use Saso\Infrastructure\Auth\GuzzleAuth0ManagementApi;
use Saso\Infrastructure\MobileConnect\PdoDeviceTokenRepository;
use saso\framework\DTO;
use saso\framework\Output;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\DbFinder;
use saso\repository\DBConnection;
use saso\util\monad\Either;

final class MyPageUsecase implements Usecase
{
    use Output;

    private DTO $output;

    public function __construct(
        private DbFinder $finder,
        private Presenter $presenter,
        private string $memberId,
    ) {
    }

    public function handle(DTO $data): void
    {
        $member = $this->finder->current(
            new \saso\repository\member\FindOne(),
            ['id' => $this->memberId]
        )->getOrElse(null);

        if ($member === null) {
            $this->output = new MyPageErrorOutput('Member not found');
            return;
        }

        $apiBaseUrl = $this->computeApiBaseUrl();
        [$passkeys, $passkeyStatus] = $this->loadPasskeys($this->memberId);

        $this->output = new MyPageOutput(
            member: $member,
            authMethods: $this->loadAuthMethods($this->memberId),
            availableProviders: $this->loadAvailableProviders($this->memberId),
            passkeys: $passkeys,
            passkeyStatus: $passkeyStatus,
            devices: $this->loadDevices($this->memberId),
            apiBaseUrl: $apiBaseUrl,
            apiDocsUrl: $apiBaseUrl.'/docs',
            openApiUrl: $apiBaseUrl.'/openapi.yaml',
            defaultScopes: DeviceToken::DEFAULT_SCOPES,
            isAdmin: $member->role === 'admin',
        );
    }

    private function computeApiBaseUrl(): string
    {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            ? 'https'
            : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $proto.'://'.$host.'/api/v1';
    }

    /**
     * @return list<array{id:int,device_name:string,created_at:string,last_used_at:?string,expires_at:string,scopes:list<string>}>
     */
    private function loadDevices(string $memberId): array
    {
        try {
            $repo   = new PdoDeviceTokenRepository(DBConnection::getPdo());
            $tokens = $repo->findByMemberId($memberId);
        } catch (\Throwable) {
            return [];
        }

        $rows = [];
        foreach ($tokens as $token) {
            if ($token->revoked) {
                continue;
            }
            $rows[] = [
                'id'           => $token->id,
                'device_name'  => $token->deviceName,
                'created_at'   => $token->createdAt->format('Y-m-d H:i'),
                'last_used_at' => $token->lastUsedAt?->format('Y-m-d H:i'),
                'expires_at'   => $token->expiresAt->format('Y-m-d H:i'),
                'scopes'       => $token->scopes,
            ];
        }

        return $rows;
    }

    private function loadAuthMethods(string $memberId): array
    {
        try {
            $pdo = DBConnection::getPdo();
            $stmt = $pdo->prepare(
                'SELECT p.id, p.name, p.type, e.external_subject, e.created_at, e.last_login_at
                   FROM member_external_identity e
                   INNER JOIN auth_provider p ON p.id = e.auth_provider_id
                  WHERE e.member_id = :id
                  ORDER BY e.created_at DESC'
            );
            $stmt->execute(['id' => $memberId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function loadAvailableProviders(string $memberId): array
    {
        try {
            $pdo = DBConnection::getPdo();
            $stmt = $pdo->prepare(
                "SELECT p.id, p.name, p.type
                   FROM auth_provider p
                  WHERE p.enabled = 1
                    AND p.type <> 'local'
                    AND NOT EXISTS (
                        SELECT 1 FROM member_external_identity e
                         WHERE e.auth_provider_id = p.id AND e.member_id = :id
                    )
                  ORDER BY p.name ASC"
            );
            $stmt->execute(['id' => $memberId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Returns a tuple of (passkey rows, status string).
     *
     * The status drives a banner on the template:
     *   - `ok`             — passkey list is current (may be empty)
     *   - `no_auth0_link`  — member is not linked to Auth0 (passkey card
     *                        explains they must sign in with Auth0 first)
     *   - `m2m_unavailable`— AUTH0_M2M_* env vars are not set in this
     *                        deployment; registration redirect still works
     *                        but the list cannot be loaded
     *   - `unreachable`    — transient Auth0 / network failure
     *
     * @return array{0: list<array{id: string, name: string, created_at: ?string, last_used_at: ?string}>, 1: string}
     */
    private function loadPasskeys(string $memberId): array
    {
        try {
            $pdo    = DBConnection::getPdo();
            $lookup = new Auth0ProviderLookup($pdo);
            if ($lookup->findFor($memberId) === null) {
                return [[], 'no_auth0_link'];
            }
            $config = Auth0PasskeyConfig::fromEnv();
            if ($config === null) {
                return [[], 'm2m_unavailable'];
            }
            $api = new GuzzleAuth0ManagementApi(
                new Client(['timeout' => 8.0, 'connect_timeout' => 4.0]),
                $config->domain,
                $config->clientId,
                $config->clientSecret,
            );
            $service = new Auth0PasskeyService($lookup, $api);
            $rows    = [];
            foreach ($service->listFor($memberId) as $passkey) {
                $rows[] = $passkey->toTemplateRow();
            }
            return [$rows, 'ok'];
        } catch (Auth0ManagementApiException $e) {
            if (function_exists('error_log')) {
                error_log('[saso-passkey-list] Auth0 '.$e->upstreamStatus.': '.$e->getMessage());
            }
            return [[], 'unreachable'];
        } catch (\Throwable $e) {
            if (function_exists('error_log')) {
                error_log('[saso-passkey-list] '.$e->getMessage());
            }
            return [[], 'unreachable'];
        }
    }

    public function output(): \saso\framework\View
    {
        return $this->presenter->complete(Either::of($this->output));
    }
}
