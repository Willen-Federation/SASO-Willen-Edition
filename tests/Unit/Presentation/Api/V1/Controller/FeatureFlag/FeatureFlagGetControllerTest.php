<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1\Controller\FeatureFlag;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Feature\Exception\FlagNotFoundException;
use Saso\Domain\Feature\Exception\InvalidFlagInputException;
use Saso\Domain\Feature\FeatureFlag;
use Saso\Domain\Feature\FeatureKey;
use Saso\Presentation\Api\V1\Controller\FeatureFlag\FeatureFlagGetController;
use Saso\Presentation\Api\V1\HttpRequest;

final class FeatureFlagGetControllerTest extends TestCase
{
    public function testReturnsFlag(): void
    {
        $repo       = new InMemoryFlagRepo([$this->makeFlag(id: 1, key: 'a.b')]);
        $controller = new FeatureFlagGetController($repo);

        $resp = $controller->handle(new HttpRequest(
            method: 'GET',
            path: '/api/v1/feature-flags/a.b',
            pathParams: ['key' => 'a.b'],
        ));

        self::assertSame(200, $resp->status);
        self::assertSame('a.b', $resp->body['key']);
    }

    public function testReturns404ForUnknownKey(): void
    {
        $controller = new FeatureFlagGetController(new InMemoryFlagRepo());

        $this->expectException(FlagNotFoundException::class);
        $controller->handle(new HttpRequest(
            method: 'GET',
            path: '/api/v1/feature-flags/missing',
            pathParams: ['key' => 'missing'],
        ));
    }

    public function testRejectsMalformedKeyAs400(): void
    {
        $controller = new FeatureFlagGetController(new InMemoryFlagRepo());

        $this->expectException(InvalidFlagInputException::class);
        $controller->handle(new HttpRequest(
            method: 'GET',
            path: '/api/v1/feature-flags/BAD-KEY',
            pathParams: ['key' => 'BAD-KEY'],
        ));
    }

    private function makeFlag(int $id, string $key): FeatureFlag
    {
        $now = new DateTimeImmutable('2026-04-26 12:00:00');
        return new FeatureFlag(
            id: $id,
            key: new FeatureKey($key),
            description: 'desc',
            enabled: false,
            rolloutPercent: 0,
            conditions: null,
            errorThreshold: 0,
            errorWindowMinutes: 60,
            autoDisabledAt: null,
            autoDisableReason: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }
}
