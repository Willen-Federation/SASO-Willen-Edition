<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\Shared;

use PHPUnit\Framework\TestCase;
use Saso\Domain\Shared\ErrorCode;
use Saso\Domain\Shared\ErrorDomain;

final class ErrorCodeTest extends TestCase
{
    public function testValuesFollowTheCanonicalFormat(): void
    {
        foreach (ErrorCode::cases() as $code) {
            self::assertMatchesRegularExpression(
                '/^SASO-[A-Z]+-\d{4}$/',
                $code->value,
                sprintf('Code %s does not match SASO-DOMAIN-NNNN', $code->name),
            );
        }
    }

    /**
     * @dataProvider httpStatusCases
     */
    public function testHttpStatusForKnownCodes(ErrorCode $code, int $expected): void
    {
        self::assertSame($expected, $code->httpStatus());
    }

    /**
     * @return iterable<string, array{ErrorCode, int}>
     */
    public static function httpStatusCases(): iterable
    {
        yield 'invalid credentials → 401'   => [ErrorCode::AuthInvalidCredentials, 401];
        yield 'session expired → 401'       => [ErrorCode::AuthSessionExpired, 401];
        yield 'unauthorized → 401'          => [ErrorCode::AuthUnauthorized, 401];
        yield 'csrf mismatch → 403'         => [ErrorCode::AuthCsrfMismatch, 403];
        yield 'forbidden → 403'             => [ErrorCode::AuthForbidden, 403];
        yield 'unhandled → 500'             => [ErrorCode::InfraUnhandled, 500];
        yield 'database unavailable → 503'  => [ErrorCode::InfraDatabaseUnavailable, 503];
        yield 'storage unavailable → 503'   => [ErrorCode::InfraStorageUnavailable, 503];
        yield 'route not found → 404'       => [ErrorCode::InfraRouteNotFound, 404];
        yield 'method not allowed → 405'    => [ErrorCode::InfraMethodNotAllowed, 405];
        yield 'provider misconfigured → 503' => [ErrorCode::AuthProviderMisconfigured, 503];
        yield 'callback state mismatch → 400' => [ErrorCode::AuthCallbackStateMismatch, 400];
        yield 'callback validation failed → 400' => [ErrorCode::AuthCallbackValidationFailed, 400];
        yield 'flag not found → 404' => [ErrorCode::FlagNotFound, 404];
        yield 'ai provider not configured → 503' => [ErrorCode::AiProviderNotConfigured, 503];
        yield 'ai rate limited → 429' => [ErrorCode::AiRateLimited, 429];
        yield 'ai response malformed → 422' => [ErrorCode::AiResponseMalformed, 422];
        yield 'ai context exceeded → 422' => [ErrorCode::AiContextExceeded, 422];
        yield 'ai content policy → 422' => [ErrorCode::AiContentPolicy, 422];
    }

    public function testHttpStatusIsAlwaysClientOrServerError(): void
    {
        foreach (ErrorCode::cases() as $code) {
            $status = $code->httpStatus();
            self::assertGreaterThanOrEqual(400, $status, $code->value);
            self::assertLessThan(600, $status, $code->value);
        }
    }

    public function testDomainIsDerivedFromValue(): void
    {
        self::assertSame(ErrorDomain::Auth, ErrorCode::AuthInvalidCredentials->domain());
        self::assertSame(ErrorDomain::Infra, ErrorCode::InfraUnhandled->domain());
    }

    public function testTranslationKeyPrefix(): void
    {
        self::assertSame(
            'error.SASO-AUTH-1001',
            ErrorCode::AuthInvalidCredentials->translationKey(),
        );
    }

    public function testEveryCodeHasANonEmptyDefaultTitle(): void
    {
        foreach (ErrorCode::cases() as $code) {
            self::assertNotEmpty($code->defaultTitle(), $code->value);
        }
    }

    public function testCasesAreUnique(): void
    {
        $values = array_map(static fn (ErrorCode $c): string => $c->value, ErrorCode::cases());
        self::assertCount(count($values), array_unique($values));
    }
}
