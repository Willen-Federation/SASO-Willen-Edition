<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Application\MyPage\Passkey;

use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Application\MyPage\Passkey\Auth0ManagementApi;
use Saso\Application\MyPage\Passkey\Auth0Passkey;
use Saso\Application\MyPage\Passkey\Auth0PasskeyService;
use Saso\Application\MyPage\Passkey\Auth0ProviderLookup;

final class Auth0PasskeyServiceTest extends TestCase
{
    public function testListForReturnsEmptyWhenMemberNotLinkedToAuth0(): void
    {
        $pdo = $this->emptyPdo();
        $api = new FakeAuth0ManagementApi();
        $service = new Auth0PasskeyService(new Auth0ProviderLookup($pdo), $api);

        self::assertSame([], $service->listFor('nope_user'));
        self::assertSame([], $api->listedFor);
    }

    public function testListForCallsApiWhenLinkExists(): void
    {
        $pdo = $this->pdoWithAuth0Link('alice_001', 'auth0|alice');
        $api = new FakeAuth0ManagementApi([
            new Auth0Passkey('meth_1', 'iPhone', new DateTimeImmutable('2026-05-01T00:00:00Z'), null),
        ]);
        $service = new Auth0PasskeyService(new Auth0ProviderLookup($pdo), $api);

        $result = $service->listFor('alice_001');

        self::assertCount(1, $result);
        self::assertSame('meth_1', $result[0]->id);
        self::assertSame(['auth0|alice'], $api->listedFor);
    }

    public function testDeleteForReturnsFalseWhenNotLinked(): void
    {
        $pdo = $this->emptyPdo();
        $api = new FakeAuth0ManagementApi();
        $service = new Auth0PasskeyService(new Auth0ProviderLookup($pdo), $api);

        self::assertFalse($service->deleteFor('nope_user', 'meth_1'));
        self::assertSame([], $api->deleted);
    }

    public function testDeleteForCallsApiWhenLinked(): void
    {
        $pdo = $this->pdoWithAuth0Link('bob_002', 'auth0|bob');
        $api = new FakeAuth0ManagementApi();
        $service = new Auth0PasskeyService(new Auth0ProviderLookup($pdo), $api);

        self::assertTrue($service->deleteFor('bob_002', 'meth_xyz'));
        self::assertSame([['auth0|bob', 'meth_xyz']], $api->deleted);
    }

    private function emptyPdo(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            'CREATE TABLE auth_provider (
                id INTEGER PRIMARY KEY,
                name TEXT, type TEXT, issuer_or_metadata_url TEXT, claim_mapping TEXT, enabled INTEGER
            )',
        );
        $pdo->exec(
            'CREATE TABLE member_external_identity (
                member_id TEXT, auth_provider_id INTEGER, external_subject TEXT,
                created_at TEXT, updated_at TEXT, last_login_at TEXT
            )',
        );
        return $pdo;
    }

    private function pdoWithAuth0Link(string $memberId, string $sub): PDO
    {
        $pdo = $this->emptyPdo();
        $pdo->exec(
            "INSERT INTO auth_provider (id, name, type, issuer_or_metadata_url, claim_mapping, enabled)
             VALUES (1, 'Auth0', 'oidc', 'https://tenant.auth0.com/', '{\"_config\":{\"flavor\":\"auth0\"}}', 1)",
        );
        $pdo->prepare(
            'INSERT INTO member_external_identity (member_id, auth_provider_id, external_subject, created_at, updated_at, last_login_at)
             VALUES (:mid, 1, :sub, :now, :now, :now)',
        )->execute([
            ':mid' => $memberId,
            ':sub' => $sub,
            ':now' => '2026-05-23 00:00:00',
        ]);
        return $pdo;
    }
}

/**
 * Hand-rolled stub instead of a PHPUnit mock so the recorded interactions are
 * directly inspectable from the test methods.
 */
final class FakeAuth0ManagementApi implements Auth0ManagementApi
{
    /** @var list<string> */
    public array $listedFor = [];
    /** @var list<array{0: string, 1: string}> */
    public array $deleted = [];

    /**
     * @param list<Auth0Passkey> $listResult
     */
    public function __construct(private readonly array $listResult = [])
    {
    }

    public function listPasskeys(string $auth0UserId): array
    {
        $this->listedFor[] = $auth0UserId;
        return $this->listResult;
    }

    public function deletePasskey(string $auth0UserId, string $authenticationMethodId): void
    {
        $this->deleted[] = [$auth0UserId, $authenticationMethodId];
    }
}
