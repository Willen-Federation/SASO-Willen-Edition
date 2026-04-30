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
use Saso\Presentation\Api\V1\Controller\Auth\ProviderTestController;
use Saso\Presentation\Api\V1\HttpRequest;

final class ProviderTestControllerTest extends TestCase
{
    public function testReturns422WhenNoIssuerUrl(): void
    {
        $record = $this->makeRecord(1, 'NoUrl', null, null);
        $repo   = $this->createMock(AuthProviderRepository::class);
        $repo->method('findById')->willReturn($record);

        $ctrl     = new ProviderTestController($repo);
        $response = $ctrl->handle($this->makeRequest(1));

        self::assertSame(422, $response->status);
        $body = json_decode($response->encode(), true);
        self::assertFalse($body['ok']);
    }

    public function testThrowsWhenProviderNotFound(): void
    {
        $repo = $this->createMock(AuthProviderRepository::class);
        $repo->method('findById')->willReturn(null);

        $ctrl = new ProviderTestController($repo);

        $this->expectException(DomainException::class);
        $ctrl->handle($this->makeRequest(99));
    }

    public function testThrowsOnInvalidId(): void
    {
        $repo = $this->createMock(AuthProviderRepository::class);
        $ctrl = new ProviderTestController($repo);

        $this->expectException(DomainException::class);
        $ctrl->handle($this->makeRequest(0));
    }

    private function makeRequest(int $id): HttpRequest
    {
        return new HttpRequest(
            method: 'POST',
            path: '/api/v1/auth/providers/'.$id.'/test',
            pathParams: ['id' => (string) $id],
            query: [],
            headers: [],
            body: null,
        );
    }

    private function makeRecord(int $id, string $name, ?string $issuer, ?string $secret): AuthProviderRecord
    {
        $now = new DateTimeImmutable('2026-04-26T12:00:00Z');
        return new AuthProviderRecord(
            id: new AuthProviderId($id),
            name: $name,
            type: AuthProviderType::Oidc,
            issuerOrMetadataUrl: $issuer,
            clientId: 'client-'.$id,
            clientSecret: $secret,
            scopes: 'openid email',
            claimMapping: null,
            enabled: true,
            isDefault: false,
            createdAt: $now,
            updatedAt: $now,
        );
    }
}
