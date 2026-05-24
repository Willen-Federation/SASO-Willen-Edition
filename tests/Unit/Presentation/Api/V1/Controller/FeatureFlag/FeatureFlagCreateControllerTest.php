<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1\Controller\FeatureFlag;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Feature\Exception\InvalidFlagInputException;
use Saso\Domain\Feature\FeatureFlag;
use Saso\Domain\Feature\FeatureKey;
use Saso\Domain\Shared\ErrorCode;
use Saso\Presentation\Api\V1\Controller\FeatureFlag\FeatureFlagCreateController;
use Saso\Presentation\Api\V1\HttpRequest;

final class FeatureFlagCreateControllerTest extends TestCase
{
    public function testCreatesAndReturns201(): void
    {
        $repo  = new InMemoryFlagRepo();
        $audit = new InMemoryAuditRepo();

        $controller = new FeatureFlagCreateController(
            $repo,
            $audit,
            static fn (): string => 'admin-7',
        );

        $resp = $controller->handle(new HttpRequest(
            method: 'POST',
            path: '/api/v1/feature-flags',
            body: (string) json_encode([
                'key'            => 'checkout.new_flow',
                'description'    => 'New checkout',
                'enabled'        => true,
                'rolloutPercent' => 50,
            ]),
        ));

        self::assertSame(201, $resp->status);
        self::assertSame('checkout.new_flow', $resp->body['key']);
        self::assertTrue($resp->body['enabled']);
        self::assertSame(50, $resp->body['rolloutPercent']);
        self::assertCount(1, $repo->saved);
    }

    public function testAuditsWhenEnabledOnCreate(): void
    {
        $repo  = new InMemoryFlagRepo();
        $audit = new InMemoryAuditRepo();

        $controller = new FeatureFlagCreateController(
            $repo,
            $audit,
            static fn (): string => 'admin-7',
        );

        $controller->handle(new HttpRequest(
            method: 'POST',
            path: '/api/v1/feature-flags',
            body: (string) json_encode([
                'key'         => 'a.b',
                'description' => 'desc',
                'enabled'     => true,
            ]),
        ));

        self::assertCount(1, $audit->records);
        self::assertSame('admin-7', $audit->records[0]['by']);
        self::assertFalse($audit->records[0]['old']);
        self::assertTrue($audit->records[0]['new']);
    }

    public function testDoesNotAuditWhenCreatedDisabled(): void
    {
        $repo  = new InMemoryFlagRepo();
        $audit = new InMemoryAuditRepo();

        $controller = new FeatureFlagCreateController(
            $repo,
            $audit,
            static fn (): string => 'admin-7',
        );

        $controller->handle(new HttpRequest(
            method: 'POST',
            path: '/api/v1/feature-flags',
            body: (string) json_encode([
                'key'         => 'a.b',
                'description' => 'desc',
                'enabled'     => false,
            ]),
        ));

        self::assertSame([], $audit->records);
    }

    public function testRejectsDuplicateKeyAs400(): void
    {
        $existing = $this->makeFlag(id: 1, key: 'dupe.flag');
        $repo     = new InMemoryFlagRepo([$existing]);

        $controller = new FeatureFlagCreateController($repo);

        $this->expectException(InvalidFlagInputException::class);
        try {
            $controller->handle(new HttpRequest(
                method: 'POST',
                path: '/api/v1/feature-flags',
                body: (string) json_encode([
                    'key'         => 'dupe.flag',
                    'description' => 'desc',
                ]),
            ));
        } catch (InvalidFlagInputException $e) {
            self::assertSame(ErrorCode::MobileInvalidRequest, $e->errorCode());
            self::assertSame(400, $e->errorCode()->httpStatus());
            throw $e;
        }
    }

    public function testRejectsMalformedKeyAs400(): void
    {
        $controller = new FeatureFlagCreateController(new InMemoryFlagRepo());

        $this->expectException(InvalidFlagInputException::class);
        $controller->handle(new HttpRequest(
            method: 'POST',
            path: '/api/v1/feature-flags',
            body: (string) json_encode([
                'key'         => 'BAD-KEY',
                'description' => 'desc',
            ]),
        ));
    }

    public function testRejectsRolloutOutOfRangeAs400(): void
    {
        $controller = new FeatureFlagCreateController(new InMemoryFlagRepo());

        $this->expectException(InvalidFlagInputException::class);
        $controller->handle(new HttpRequest(
            method: 'POST',
            path: '/api/v1/feature-flags',
            body: (string) json_encode([
                'key'            => 'a.b',
                'description'    => 'desc',
                'rolloutPercent' => 150,
            ]),
        ));
    }

    public function testRejectsScalarConditionsAs400(): void
    {
        $controller = new FeatureFlagCreateController(new InMemoryFlagRepo());

        $this->expectException(InvalidFlagInputException::class);
        $controller->handle(new HttpRequest(
            method: 'POST',
            path: '/api/v1/feature-flags',
            body: (string) json_encode([
                'key'         => 'a.b',
                'description' => 'desc',
                'conditions'  => 'not-a-map',
            ]),
        ));
    }

    public function testRejectsEmptyDescriptionAs400(): void
    {
        $controller = new FeatureFlagCreateController(new InMemoryFlagRepo());

        $this->expectException(InvalidFlagInputException::class);
        $controller->handle(new HttpRequest(
            method: 'POST',
            path: '/api/v1/feature-flags',
            body: (string) json_encode([
                'key'         => 'a.b',
                'description' => '',
            ]),
        ));
    }

    public function testAssignsNextIdFromRepo(): void
    {
        $repo = new InMemoryFlagRepo([
            $this->makeFlag(id: 5, key: 'existing'),
        ]);

        $controller = new FeatureFlagCreateController($repo);
        $resp       = $controller->handle(new HttpRequest(
            method: 'POST',
            path: '/api/v1/feature-flags',
            body: (string) json_encode([
                'key'         => 'a.b',
                'description' => 'desc',
            ]),
        ));

        self::assertSame(6, $resp->body['id']);
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
