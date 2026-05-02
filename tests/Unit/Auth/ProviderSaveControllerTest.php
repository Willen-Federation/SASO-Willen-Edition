<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use saso\auth\ProviderSaveController;

/**
 * Regression coverage for the URL builders used by /auth/providerNew.
 *
 * The "認証プロバイダーが正しく設定されていません" error operators were seeing
 * after submitting the Auth0 form was caused by an "https://https://…" issuer
 * URL produced when the operator pasted the value from Auth0's dashboard
 * (which always renders it with a scheme). The builders below normalise
 * inputs so the saved row is always a valid URL.
 */
final class ProviderSaveControllerTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string}> */
    public static function auth0DomainSamples(): array
    {
        return [
            'plain domain'                => ['example.auth0.com', 'https://example.auth0.com'],
            'with https scheme'           => ['https://example.auth0.com', 'https://example.auth0.com'],
            'with http scheme downgraded' => ['http://example.auth0.com', 'https://example.auth0.com'],
            'mixed case scheme'           => ['HTTPS://example.auth0.com', 'https://example.auth0.com'],
            'trailing slash'              => ['https://example.auth0.com/', 'https://example.auth0.com'],
            'multiple trailing slashes'   => ['example.auth0.com///', 'https://example.auth0.com'],
            'whitespace padding'          => ['   example.auth0.com   ', 'https://example.auth0.com'],
            'realm path preserved'        => ['example.auth0.com/realms/foo', 'https://example.auth0.com/realms/foo'],
            'empty input'                 => ['', ''],
            'whitespace only'             => ["\t  \n", ''],
            'protocol only'               => ['https://', ''],
        ];
    }

    /**
     * @dataProvider auth0DomainSamples
     */
    public function testBuildAuth0IssuerUrl(string $input, string $expected): void
    {
        self::assertSame($expected, ProviderSaveController::buildAuth0IssuerUrl($input));
    }

    public function testBuildCognitoIssuerUrl(): void
    {
        self::assertSame(
            'https://cognito-idp.ap-northeast-1.amazonaws.com/ap-northeast-1_XXXX',
            ProviderSaveController::buildCognitoIssuerUrl('ap-northeast-1', 'ap-northeast-1_XXXX'),
        );
    }

    public function testBuildCognitoIssuerUrlReturnsEmptyWhenIncomplete(): void
    {
        self::assertSame('', ProviderSaveController::buildCognitoIssuerUrl('', 'pool'));
        self::assertSame('', ProviderSaveController::buildCognitoIssuerUrl('region', ''));
    }

    public function testBuildFirebaseIssuerUrl(): void
    {
        self::assertSame(
            'https://securetoken.google.com/my-project-12345',
            ProviderSaveController::buildFirebaseIssuerUrl('my-project-12345'),
        );
        self::assertSame('', ProviderSaveController::buildFirebaseIssuerUrl(''));
        self::assertSame('', ProviderSaveController::buildFirebaseIssuerUrl('   '));
    }

    public function testNormalizeUrlTrimsButPreservesValue(): void
    {
        self::assertSame(
            'https://idp.example.com/.well-known/openid-configuration',
            ProviderSaveController::normalizeUrl(
                "  https://idp.example.com/.well-known/openid-configuration\n",
            ),
        );
        self::assertSame('', ProviderSaveController::normalizeUrl(''));
    }

    public function testEndToEndAuth0InputRoundTrip(): void
    {
        $controller = new ProviderSaveController([
            'provider_template' => 'auth0',
            'provider_name'     => 'Auth0 本番',
            'auth0_domain'      => 'https://example.auth0.com/',
            'client_id'         => 'abcd1234',
            'client_secret'     => 'shh',
        ]);
        $reflection = new \ReflectionClass($controller);
        $prop       = $reflection->getProperty('data');
        $prop->setAccessible(true);
        $data = $prop->getValue($controller);

        $issuer = (new \ReflectionClass($data))->getProperty('issuerUrl');
        $issuer->setAccessible(true);
        self::assertSame('https://example.auth0.com', $issuer->getValue($data));
    }
}
