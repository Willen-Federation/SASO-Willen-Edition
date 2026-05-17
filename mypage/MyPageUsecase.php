<?php

namespace saso\mypage;

use Saso\Domain\MobileConnect\DeviceToken;
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

        $this->output = new MyPageOutput(
            member: $member,
            authMethods: $this->loadAuthMethods($this->memberId),
            availableProviders: $this->loadAvailableProviders($this->memberId),
            passkeys: $this->loadPasskeys($this->memberId),
            devices: $this->loadDevices($this->memberId),
            apiBaseUrl: $apiBaseUrl,
            apiDocsUrl: $apiBaseUrl.'/docs',
            openApiUrl: $apiBaseUrl.'/openapi.yaml',
            defaultScopes: DeviceToken::DEFAULT_SCOPES,
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

    private function loadPasskeys(string $memberId): array
    {
        try {
            $pdo = DBConnection::getPdo();
            $stmt = $pdo->prepare(
                'SELECT id, name, created_at, last_used_at FROM webauthn_credential WHERE member_id = :id ORDER BY created_at DESC'
            );
            $stmt->execute(['id' => $memberId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function output(): \saso\framework\View
    {
        return $this->presenter->complete(Either::of($this->output));
    }
}
