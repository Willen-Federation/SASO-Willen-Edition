<?php

namespace saso\mypage;

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

        $this->output = new MyPageOutput(
            member: $member,
            authMethods: $this->loadAuthMethods($this->memberId),
            availableProviders: $this->loadAvailableProviders($this->memberId),
            passkeys: $this->loadPasskeys($this->memberId),
        );
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
