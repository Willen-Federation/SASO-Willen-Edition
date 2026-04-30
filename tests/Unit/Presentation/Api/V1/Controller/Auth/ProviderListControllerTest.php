<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1\Controller\Auth;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\AuthProviderRecord;
use Saso\Domain\Auth\AuthProviderType;
use Saso\Domain\Auth\Repository\AuthProviderRepository;
use Saso\Presentation\Api\V1\Controller\Auth\ProviderListController;
use Saso\Presentation\Api\V1\HttpRequest;

final class ProviderListControllerTest extends TestCase
{
    public function testReturnsEmptyListWhenNoProviders(): void
    {
        $repo = $this->createMock(AuthProviderRepository::class);
        $repo->method('listAll')->willReturn([]);

        $ctrl     = new ProviderListController($repo);
        $response = $ctrl->handle($this->makeRequest());

        $body = json_decode($response->encode(), true);
        self::assertIsArray($body);
        self::assertSame(0, $body['total']);
        self::assertSame([], $body['data']);
    }

    public function testReturnsProviderWithoutSecret(): void
    {
        $record = $this->makeRecord(1, 'Auth0 Staff', 'topsecret');

        $repo = $this->createMock(AuthProviderRepository::class);
        $repo->method('listAll')->willReturn([$record]);

        $ctrl     = new ProviderListController($repo);
        $response = $ctrl->handle($this->makeRequest());

        $body = json_decode($response->encode(), true);
        self::assertSame(1, $body['total']);
        $item = $body['data'][0];
        self::assertSame(1, $item['id']);
        self::assertSame('Auth0 Staff', $item['name']);
        self::assertArrayNotHasKey('clientSecret', $item);
        self::assertTrue($item['hasSecret']);
    }

    public function testHasSecretFalseWhenNoSecret(): void
    {
        $record = $this->makeRecord(1, 'Local', null);

        $repo = $this->createMock(AuthProviderRepository::class);
        $repo->method('listAll')->willReturn([$record]);

        $ctrl     = new ProviderListController($repo);
        $response = $ctrl->handle($this->makeRequest());

        $body = json_decode($response->encode(), true);
        self::assertFalse($body['data'][0]['hasSecret']);
    }

    private function makeRequest(): HttpRequest
    {
        return new HttpRequest(
            method: 'GET',
            path: '/api/v1/auth/providers',
            pathParams: [],
            query: [],
            headers: [],
            body: null,
        );
    }

    private function makeRecord(int $id, string $name, ?string $secret): AuthProviderRecord
    {
        $now = new DateTimeImmutable('2026-04-26T12:00:00Z');
        return new AuthProviderRecord(
            id: new AuthProviderId($id),
            name: $name,
            type: AuthProviderType::Oidc,
            issuerOrMetadataUrl: 'https://example.test/.well-known/openid-configuration',
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
