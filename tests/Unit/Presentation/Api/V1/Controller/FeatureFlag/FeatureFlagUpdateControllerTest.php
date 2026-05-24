<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1\Controller\FeatureFlag;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Feature\Exception\FlagNotFoundException;
use Saso\Domain\Feature\Exception\InvalidFlagInputException;
use Saso\Domain\Feature\FeatureFlag;
use Saso\Domain\Feature\FeatureKey;
use Saso\Domain\Shared\ErrorCode;
use Saso\Presentation\Api\V1\Controller\FeatureFlag\FeatureFlagUpdateController;
use Saso\Presentation\Api\V1\HttpRequest;

final class FeatureFlagUpdateControllerTest extends TestCase
{
    public function testUpdatesEnabled(): void
    {
        $repo  = new InMemoryFlagRepo([$this->makeFlag(id: 1, key: 'a.b', enabled: false)]);
        $audit = new InMemoryAuditRepo();

        $controller = new FeatureFlagUpdateController(
            $repo,
            $audit,
            static fn (): string => 'admin-9',
        );

        $resp = $controller->handle(new HttpRequest(
            method: 'PATCH',
            path: '/api/v1/feature-flags/a.b',
            pathParams: ['key' => 'a.b'],
            body: (string) json_encode(['enabled' => true]),
        ));

        self::assertSame(200, $resp->status);
        self::assertTrue($resp->body['enabled']);
        self::assertCount(1, $audit->records);
        self::assertSame('admin-9', $audit->records[0]['by']);
        self::assertFalse($audit->records[0]['old']);
        self::assertTrue($audit->records[0]['new']);
    }

    public function testDoesNotAuditWhenEnabledUnchanged(): void
    {
        $repo  = new InMemoryFlagRepo([$this->makeFlag(id: 1, key: 'a.b', enabled: true)]);
        $audit = new InMemoryAuditRepo();

        $controller = new FeatureFlagUpdateController($repo, $audit);

        $controller->handle(new HttpRequest(
            method: 'PATCH',
            path: '/api/v1/feature-flags/a.b',
            pathParams: ['key' => 'a.b'],
            body: (string) json_encode(['rolloutPercent' => 25]),
        ));

        self::assertSame([], $audit->records);
    }

    public function testRejectsMalformedKeyAs400(): void
    {
        $controller = new FeatureFlagUpdateController(new InMemoryFlagRepo());

        $this->expectException(InvalidFlagInputException::class);
        try {
            $controller->handle(new HttpRequest(
                method: 'PATCH',
                path: '/api/v1/feature-flags/BAD-KEY',
                pathParams: ['key' => 'BAD-KEY'],
                body: '{"enabled":true}',
            ));
        } catch (InvalidFlagInputException $e) {
            self::assertSame(400, $e->errorCode()->httpStatus());
            self::assertSame(ErrorCode::MobileInvalidRequest, $e->errorCode());
            throw $e;
        }
    }

    public function testRejectsOutOfRangeRolloutAs400(): void
    {
        $repo       = new InMemoryFlagRepo([$this->makeFlag(id: 1, key: 'a.b')]);
        $controller = new FeatureFlagUpdateController($repo);

        $this->expectException(InvalidFlagInputException::class);
        $controller->handle(new HttpRequest(
            method: 'PATCH',
            path: '/api/v1/feature-flags/a.b',
            pathParams: ['key' => 'a.b'],
            body: (string) json_encode(['rolloutPercent' => -1]),
        ));
    }

    public function testReturns404ForUnknownKey(): void
    {
        $controller = new FeatureFlagUpdateController(new InMemoryFlagRepo());

        $this->expectException(FlagNotFoundException::class);
        $controller->handle(new HttpRequest(
            method: 'PATCH',
            path: '/api/v1/feature-flags/nope.flag',
            pathParams: ['key' => 'nope.flag'],
            body: '{"enabled":true}',
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
