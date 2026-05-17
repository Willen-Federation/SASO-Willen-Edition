<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Http\Problem;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Shared\ErrorCode;
use Saso\Presentation\Http\Problem\ProblemDetails;
use Saso\Presentation\Http\Problem\ProblemRenderer;

final class ProblemRendererTest extends TestCase
{
    public function testEncodeProducesValidJsonWithRfc7807Fields(): void
    {
        $p = ProblemDetails::fromError(
            code: ErrorCode::AuthInvalidCredentials,
            title: 'Invalid credentials',
            detail: 'wrong password',
            instance: '/login',
            traceId: '1f9b3c8a-9e15-4d6a-8c2c-3d8f4f1f7a12',
        );

        $body = (new ProblemRenderer())->encode($p);

        $decoded = json_decode($body, associative: true);
        self::assertIsArray($decoded);
        self::assertSame('SASO-AUTH-1001', $decoded['code']);
        self::assertSame(401, $decoded['status']);
        self::assertSame('1f9b3c8a-9e15-4d6a-8c2c-3d8f4f1f7a12', $decoded['traceId']);
    }

    public function testEncodeKeepsForwardSlashesUnescaped(): void
    {
        $p = ProblemDetails::fromError(
            code: ErrorCode::InfraUnhandled,
            title: 'Internal server error',
            detail: 'd',
            instance: '/api/v1/items',
            traceId: 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee',
        );

        $body = (new ProblemRenderer())->encode($p);

        self::assertStringContainsString('"instance":"/api/v1/items"', $body);
        self::assertStringNotContainsString('\/', $body);
    }

    public function testBearerChallengeIsEmittedForMobile401(): void
    {
        $p = new ProblemDetails(
            type: 'https://example.test/#SASO-MOBILE-2002',
            title: 'Invalid token',
            status: 401,
            detail: 'Bearer rejected',
            instance: '/api/v1/items',
            code: 'SASO-MOBILE-2002',
            traceId: 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee',
        );

        self::assertSame(
            'Bearer realm="api", error="invalid_token"',
            (new ProblemRenderer())->bearerChallenge($p),
        );
    }

    public function testBearerChallengeForScopeInsufficientCarriesScopeWhenProvided(): void
    {
        $p = new ProblemDetails(
            type: 'https://example.test/#SASO-MOBILE-2008',
            title: 'Scope insufficient',
            status: 403,
            detail: 'Token lacks scope',
            instance: '/api/v1/items',
            code: 'SASO-MOBILE-2008',
            traceId: 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee',
            extensions: ['requiredScope' => 'items:write'],
        );

        self::assertSame(
            'Bearer realm="api", error="insufficient_scope", scope="items:write"',
            (new ProblemRenderer())->bearerChallenge($p),
        );
    }

    public function testBearerChallengeReturnsNullForNonMobileFailures(): void
    {
        $p = ProblemDetails::fromError(
            code: ErrorCode::AuthInvalidCredentials,
            title: 'Invalid credentials',
            detail: 'wrong password',
            instance: '/login',
            traceId: 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee',
        );

        self::assertNull((new ProblemRenderer())->bearerChallenge($p));
    }

    public function testEncodeKeepsMultibyteCharactersUnescaped(): void
    {
        $p = ProblemDetails::fromError(
            code: ErrorCode::AuthInvalidCredentials,
            title: '認証情報が無効です',
            detail: 'パスワードが一致しません',
            instance: '/api/v1/auth/login',
            traceId: 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee',
        );

        $body = (new ProblemRenderer())->encode($p);

        self::assertStringContainsString('認証情報が無効です', $body);
        self::assertStringNotContainsString('\u', $body);
    }
}
