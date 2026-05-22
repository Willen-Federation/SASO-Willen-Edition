<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Infrastructure\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Saso\Application\MyPage\Passkey\Auth0ManagementApiException;
use Saso\Infrastructure\Auth\GuzzleAuth0ManagementApi;

final class GuzzleAuth0ManagementApiTest extends TestCase
{
    /** @var list<array{request: \Psr\Http\Message\RequestInterface, response: \Psr\Http\Message\ResponseInterface}> */
    private array $captured = [];

    public function testListPasskeysFiltersWebAuthnTypesAndParsesTimestamps(): void
    {
        $api = $this->buildApi([
            new Response(200, [], (string) json_encode(['access_token' => 'm2m_tok'])),
            new Response(200, [], (string) json_encode([
                ['id' => 'meth_1', 'type' => 'passkey', 'name' => 'iPhone',
                    'created_at' => '2026-05-01T12:00:00.000Z',
                    'last_auth_at' => '2026-05-20T08:30:00.000Z'],
                ['id' => 'meth_2', 'type' => 'webauthn-roaming', 'name' => 'YubiKey',
                    'created_at' => '2026-05-10T00:00:00.000Z'],
                ['id' => 'meth_3', 'type' => 'totp', 'name' => 'Authy'],
            ])),
        ]);

        $result = $api->listPasskeys('auth0|alice');

        self::assertCount(2, $result, 'TOTP entry must be filtered out');
        self::assertSame('meth_1', $result[0]->id);
        self::assertSame('iPhone', $result[0]->name);
        self::assertSame('2026-05-01 12:00', $result[0]->createdAt?->format('Y-m-d H:i'));
        self::assertSame('2026-05-20 08:30', $result[0]->lastUsedAt?->format('Y-m-d H:i'));
        self::assertSame('meth_2', $result[1]->id);
        self::assertNull($result[1]->lastUsedAt);

        self::assertCount(2, $this->captured);
        self::assertSame('POST', $this->captured[0]['request']->getMethod());
        self::assertSame('https://tenant.auth0.com/oauth/token', (string) $this->captured[0]['request']->getUri());
        self::assertSame('GET', $this->captured[1]['request']->getMethod());
        self::assertStringContainsString(
            '/api/v2/users/auth0%7Calice/authentication-methods',
            (string) $this->captured[1]['request']->getUri(),
        );
        self::assertSame('Bearer m2m_tok', $this->captured[1]['request']->getHeaderLine('Authorization'));
    }

    public function testDeletePasskeyCallsManagementApiWithEncodedIds(): void
    {
        $api = $this->buildApi([
            new Response(200, [], (string) json_encode(['access_token' => 'tok'])),
            new Response(204),
        ]);

        $api->deletePasskey('auth0|bob', 'meth|xyz');

        self::assertCount(2, $this->captured);
        self::assertSame('DELETE', $this->captured[1]['request']->getMethod());
        self::assertStringContainsString(
            '/api/v2/users/auth0%7Cbob/authentication-methods/meth%7Cxyz',
            (string) $this->captured[1]['request']->getUri(),
        );
    }

    public function testDeletePasskeyTreats404AsSuccess(): void
    {
        $api = $this->buildApi([
            new Response(200, [], (string) json_encode(['access_token' => 'tok'])),
            new Response(404, [], '{"error":"not_found"}'),
        ]);

        $api->deletePasskey('auth0|bob', 'gone');
        self::assertTrue(true, 'no exception was thrown for 404');
    }

    public function testDeletePasskeyRefusesEmptyId(): void
    {
        $api = $this->buildApi([]);

        $this->expectException(Auth0ManagementApiException::class);
        $this->expectExceptionMessage('empty id');

        $api->deletePasskey('auth0|bob', '');
    }

    public function testTokenMintFailureSurfacesAsException(): void
    {
        $api = $this->buildApi([
            new Response(401, [], '{"error":"unauthorized"}'),
        ]);

        try {
            $api->listPasskeys('auth0|alice');
            self::fail('Expected Auth0ManagementApiException');
        } catch (Auth0ManagementApiException $e) {
            self::assertSame(401, $e->upstreamStatus);
            self::assertStringContainsString('M2M token mint failed', $e->getMessage());
        }
    }

    public function testListPasskeysServerErrorSurfacesAsException(): void
    {
        $api = $this->buildApi([
            new Response(200, [], (string) json_encode(['access_token' => 'tok'])),
            new Response(503, [], '{"error":"service_unavailable"}'),
        ]);

        try {
            $api->listPasskeys('auth0|alice');
            self::fail('Expected Auth0ManagementApiException');
        } catch (Auth0ManagementApiException $e) {
            self::assertSame(503, $e->upstreamStatus);
        }
    }

    public function testTokenIsCachedAcrossCalls(): void
    {
        $api = $this->buildApi([
            new Response(200, [], (string) json_encode(['access_token' => 'tok'])),
            new Response(200, [], (string) json_encode([])),
            new Response(204),
        ]);

        $api->listPasskeys('auth0|alice');
        $api->deletePasskey('auth0|alice', 'meth_1');

        // One token mint + two API calls; the token is reused.
        self::assertCount(3, $this->captured);
        self::assertSame('POST', $this->captured[0]['request']->getMethod());
        self::assertSame('GET', $this->captured[1]['request']->getMethod());
        self::assertSame('DELETE', $this->captured[2]['request']->getMethod());
    }

    /**
     * @param list<Response> $responses
     */
    private function buildApi(array $responses): GuzzleAuth0ManagementApi
    {
        $this->captured = [];
        $mock           = new MockHandler($responses);
        $stack          = HandlerStack::create($mock);
        $stack->push(Middleware::history($this->captured));
        $client = new Client(['handler' => $stack]);

        return new GuzzleAuth0ManagementApi(
            $client,
            'tenant.auth0.com',
            'm2m_client_id',
            'm2m_client_secret',
        );
    }
}
