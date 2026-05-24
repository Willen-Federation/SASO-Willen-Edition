<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1\Controller\FeatureFlag;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Feature\FeatureFlag;
use Saso\Domain\Feature\FeatureKey;
use Saso\Presentation\Api\V1\Controller\FeatureFlag\FeatureFlagListController;
use Saso\Presentation\Api\V1\HttpRequest;

final class FeatureFlagListControllerTest extends TestCase
{
    public function testReturnsList(): void
    {
        $repo = new InMemoryFlagRepo([
            $this->makeFlag(id: 1, key: 'a.b'),
            $this->makeFlag(id: 2, key: 'c.d'),
        ]);

        $controller = new FeatureFlagListController($repo);
        $resp       = $controller->handle(new HttpRequest(
            method: 'GET',
            path: '/api/v1/feature-flags',
        ));

        self::assertSame(200, $resp->status);
        self::assertSame(2, $resp->body['total']);
        self::assertCount(2, $resp->body['data']);
    }

    public function testEmptyListIsValid(): void
    {
        $controller = new FeatureFlagListController(new InMemoryFlagRepo());

        $resp = $controller->handle(new HttpRequest(
            method: 'GET',
            path: '/api/v1/feature-flags',
        ));

        self::assertSame(0, $resp->body['total']);
        self::assertSame([], $resp->body['data']);
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
