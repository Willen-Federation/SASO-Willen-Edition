<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1\Controller\FeatureFlag;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Feature\Exception\FlagNotFoundException;
use Saso\Domain\Feature\Exception\InvalidFlagInputException;
use Saso\Domain\Feature\FeatureFlag;
use Saso\Domain\Feature\FeatureKey;
use Saso\Presentation\Api\V1\Controller\FeatureFlag\FeatureFlagDeleteController;
use Saso\Presentation\Api\V1\HttpRequest;

final class FeatureFlagDeleteControllerTest extends TestCase
{
    public function testDeletesAndReturns204(): void
    {
        $repo  = new InMemoryFlagRepo([$this->makeFlag(id: 1, key: 'a.b', enabled: true)]);
        $audit = new InMemoryAuditRepo();

        $controller = new FeatureFlagDeleteController(
            $repo,
            $audit,
            static fn (): string => 'admin-3',
        );

        $resp = $controller->handle(new HttpRequest(
            method: 'DELETE',
            path: '/api/v1/feature-flags/a.b',
            pathParams: ['key' => 'a.b'],
        ));

        self::assertSame(204, $resp->status);
        self::assertSame([1], $repo->deleted);
        self::assertCount(1, $audit->records);
        self::assertSame('admin-3', $audit->records[0]['by']);
        self::assertTrue($audit->records[0]['old']);
        self::assertFalse($audit->records[0]['new']);
    }

    public function testReturns404ForUnknownKey(): void
    {
        $controller = new FeatureFlagDeleteController(new InMemoryFlagRepo());

        $this->expectException(FlagNotFoundException::class);
        $controller->handle(new HttpRequest(
            method: 'DELETE',
            path: '/api/v1/feature-flags/missing',
            pathParams: ['key' => 'missing'],
        ));
    }

    public function testRejectsMalformedKeyAs400(): void
    {
        $controller = new FeatureFlagDeleteController(new InMemoryFlagRepo());

        $this->expectException(InvalidFlagInputException::class);
        $controller->handle(new HttpRequest(
            method: 'DELETE',
            path: '/api/v1/feature-flags/BAD-KEY',
            pathParams: ['key' => 'BAD-KEY'],
        ));
    }

    private function makeFlag(int $id, string $key, bool $enabled = false): FeatureFlag
    {
        $now = new DateTimeImmutable('2026-04-26 12:00:00');
        return new FeatureFlag(
            id: $id,
            key: new FeatureKey($key),
            description: 'desc',
            enabled: $enabled,
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
