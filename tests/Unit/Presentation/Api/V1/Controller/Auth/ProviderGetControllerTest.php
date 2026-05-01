<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1\Controller\Auth;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\AuthProviderRecord;
use Saso\Domain\Auth\AuthProviderType;
use Saso\Domain\Auth\Repository\AuthProviderRepository;
use Saso\Domain\Shared\DomainException;
use Saso\Presentation\Api\V1\Controller\Auth\ProviderGetController;
use Saso\Presentation\Api\V1\HttpRequest;

final class ProviderGetControllerTest extends TestCase
{
    public function testReturns200WithProviderData(): void
    {
        $record = $this->makeRecord(42, 'Cognito HR');
        $repo   = $this->createMock(AuthProviderRepository::class);
        $repo->method('findById')->willReturn($record);

        $ctrl     = new ProviderGetController($repo);
        $response = $ctrl->handle($this->makeRequest(42));

        $body = json_decode($response->encode(), true);
        self::assertSame(42, $body['id']);
        self::assertSame('Cognito HR', $body['name']);
        self::assertSame('oidc', $body['type']);
    }

    public function testThrowsWhenProviderNotFound(): void
    {
        $repo = $this->createMock(AuthProviderRepository::class);
        $repo->method('findById')->willReturn(null);

        $ctrl = new ProviderGetController($repo);

        $this->expectException(DomainException::class);
        $ctrl->handle($this->makeRequest(999));
    }

    public function testThrowsOnInvalidId(): void
    {
        $repo = $this->createMock(AuthProviderRepository::class);
        $ctrl = new ProviderGetController($repo);

        $this->expectException(DomainException::class);
        $ctrl->handle($this->makeRequest(0));
    }

    private function makeRequest(int $id): HttpRequest
    {
        return new HttpRequest(
            method: 'GET',
            path: '/api/v1/auth/providers/'.$id,
            pathParams: ['id' => (string) $id],
            query: [],
            headers: [],
            body: null,
        );
    }

    private function makeRecord(int $id, string $name): AuthProviderRecord
    {
        $now = new DateTimeImmutable('2026-04-26T12:00:00Z');
        return new AuthProviderRecord(
            id: new AuthProviderId($id),
            name: $name,
            type: AuthProviderType::Oidc,
            issuerOrMetadataUrl: 'https://example.test/.well-known/openid-configuration',
            clientId: 'client-'.$id,
            clientSecret: 'secret',
            scopes: 'openid email',
            claimMapping: null,
            enabled: true,
            isDefault: false,
            createdAt: $now,
            updatedAt: $now,
        );
    }
}
