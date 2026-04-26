<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Http\Problem;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Shared\ErrorCode;
use Saso\Presentation\Http\Problem\ProblemDetails;

final class ProblemDetailsTest extends TestCase
{
    public function testFromErrorPopulatesEveryStandardField(): void
    {
        $p = ProblemDetails::fromError(
            code: ErrorCode::AuthInvalidCredentials,
            title: 'Invalid credentials',
            detail: 'The submitted password did not match.',
            instance: '/api/v1/auth/login',
            traceId: '1f9b3c8a-9e15-4d6a-8c2c-3d8f4f1f7a12',
        );

        self::assertStringEndsWith('SASO-AUTH-1001', $p->type);
        self::assertSame('Invalid credentials', $p->title);
        self::assertSame(401, $p->status);
        self::assertSame('The submitted password did not match.', $p->detail);
        self::assertSame('/api/v1/auth/login', $p->instance);
        self::assertSame('SASO-AUTH-1001', $p->code);
        self::assertSame('1f9b3c8a-9e15-4d6a-8c2c-3d8f4f1f7a12', $p->traceId);
    }

    public function testFromErrorAcceptsOverriddenTypeBaseUrl(): void
    {
        $p = ProblemDetails::fromError(
            code: ErrorCode::AuthCsrfMismatch,
            title: 'CSRF token mismatch',
            detail: 'token did not validate',
            instance: '/api/v1/items',
            traceId: 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee',
            typeBaseUrl: 'https://example.test/errors#',
        );

        self::assertSame(
            'https://example.test/errors#SASO-AUTH-1003',
            $p->type,
        );
    }

    public function testToArrayShapeMatchesRfc7807(): void
    {
        $p = ProblemDetails::fromError(
            code: ErrorCode::InfraUnhandled,
            title: 'Internal server error',
            detail: 'unexpected',
            instance: '/api/v1/anything',
            traceId: 'deadbeef-0000-4000-8000-000000000000',
        );

        $arr = $p->toArray();

        self::assertSame(
            ['type', 'title', 'status', 'detail', 'instance', 'code', 'traceId'],
            array_keys($arr),
        );
    }

    public function testExtensionsAreMergedAfterStandardFields(): void
    {
        $p = new ProblemDetails(
            type: ProblemDetails::DEFAULT_TYPE_BASE_URL.'SASO-INFRA-9000',
            title: 't',
            status: 500,
            detail: 'd',
            instance: '/x',
            code: 'SASO-INFRA-9000',
            traceId: 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee',
            extensions: ['retryable' => false, 'category' => 'server'],
        );

        $arr = $p->toArray();

        self::assertArrayHasKey('retryable', $arr);
        self::assertFalse($arr['retryable']);
        self::assertSame('server', $arr['category']);
    }

    public function testStatusMatchesErrorCodeMapping(): void
    {
        $p = ProblemDetails::fromError(
            code: ErrorCode::InfraDatabaseUnavailable,
            title: 't',
            detail: 'd',
            instance: '/x',
            traceId: 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee',
        );

        self::assertSame(503, $p->status);
    }
}
