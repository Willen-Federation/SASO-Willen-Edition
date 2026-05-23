<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1\Controller\Auth;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Application\Auth\RateLimiter;
use Saso\Application\Auth\VerifyCredentialsService;
use Saso\Application\Mobile\JwtGuard;
use Saso\Domain\Auth\Exception\CurrentPasswordMismatchException;
use Saso\Domain\Auth\Exception\MalformedRequestException;
use Saso\Domain\Auth\Exception\PasswordPolicyViolationException;
use Saso\Domain\MobileConnect\DeviceToken;
use Saso\Domain\MobileConnect\Jwt\JwtService;
use Saso\Domain\MobileConnect\Repository\DeviceTokenRepository;
use saso\entity\Member;
use Saso\Presentation\Api\V1\Controller\Auth\PasswordChangeController;
use Saso\Presentation\Api\V1\HttpRequest;

final class PasswordChangeControllerTest extends TestCase
{
    private const JWT_SECRET = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private PDO $pdo;
    private JwtService $jwt;
    private VerifyCredentialsService $credentials;
    private RateLimiter $limiter;
    private string $tempDir = '';

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(
            'CREATE TABLE Member (
                id VARCHAR(20) NOT NULL PRIMARY KEY,
                password VARCHAR(255) NOT NULL,
                userName VARCHAR(50) NOT NULL
            )',
        );

        $this->jwt         = new JwtService(self::JWT_SECRET);
        $this->credentials = new VerifyCredentialsService($this->pdo);

        $this->tempDir = sys_get_temp_dir().'/saso-pwchange-test-'.bin2hex(random_bytes(4));
        $this->limiter = new RateLimiter($this->tempDir, maxAttempts: 3, windowSeconds: 60);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            foreach (scandir($this->tempDir) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                @unlink($this->tempDir.'/'.$entry);
            }
            @rmdir($this->tempDir);
        }
    }

    public function testHappyPathReturns204AndChangesPassword(): void
    {
        $this->seed('alice12345', 'oldpassword1');

        $tokenRepo = $this->createMock(DeviceTokenRepository::class);
        $tokenRepo->method('findByMemberId')->with('alice12345')->willReturn([]);

        $controller = $this->makeController($tokenRepo);

        $response = $controller->handle($this->makeRequest(
            memberId: 'alice12345',
            deviceId: 1,
            body: ['currentPassword' => 'oldpassword1', 'newPassword' => 'newpassword2'],
        ));

        self::assertSame(204, $response->status);
        // Verify the new password is now stored, and old is rejected.
        self::assertSame('alice12345', $this->credentials->verify('alice12345', 'newpassword2')['id']);
        $this->expectException(\Saso\Domain\Auth\Exception\InvalidCredentialsException::class);
        $this->credentials->verify('alice12345', 'oldpassword1');
    }

    public function testWrongCurrentPasswordReturns401(): void
    {
        $this->seed('alice12345', 'oldpassword1');

        $tokenRepo  = $this->createMock(DeviceTokenRepository::class);
        $controller = $this->makeController($tokenRepo);

        $this->expectException(CurrentPasswordMismatchException::class);
        $controller->handle($this->makeRequest(
            memberId: 'alice12345',
            deviceId: 1,
            body: ['currentPassword' => 'wrong-password', 'newPassword' => 'newpassword2'],
        ));
    }

    public function testPolicyViolationReturns422(): void
    {
        $this->seed('alice12345', 'oldpassword1');

        $tokenRepo  = $this->createMock(DeviceTokenRepository::class);
        $controller = $this->makeController($tokenRepo);

        $this->expectException(PasswordPolicyViolationException::class);
        $controller->handle($this->makeRequest(
            memberId: 'alice12345',
            deviceId: 1,
            // newPassword too short.
            body: ['currentPassword' => 'oldpassword1', 'newPassword' => 'short'],
        ));
    }

    public function testNewPasswordSameAsCurrentIsRejected(): void
    {
        $this->seed('alice12345', 'oldpassword1');

        $tokenRepo  = $this->createMock(DeviceTokenRepository::class);
        $controller = $this->makeController($tokenRepo);

        $this->expectException(PasswordPolicyViolationException::class);
        $controller->handle($this->makeRequest(
            memberId: 'alice12345',
            deviceId: 1,
            body: ['currentPassword' => 'oldpassword1', 'newPassword' => 'oldpassword1'],
        ));
    }

    public function testMalformedBodyReturns422(): void
    {
        $this->seed('alice12345', 'oldpassword1');
        $tokenRepo  = $this->createMock(DeviceTokenRepository::class);
        $controller = $this->makeController($tokenRepo);

        $this->expectException(MalformedRequestException::class);
        $controller->handle($this->makeRequest(
            memberId: 'alice12345',
            deviceId: 1,
            body: ['newPassword' => 'newpassword2'],
        ));
    }

    public function testRevokesOtherDevicesButKeepsCurrent(): void
    {
        $this->seed('alice12345', 'oldpassword1');

        $current = $this->makeDeviceToken(id: 1, memberId: 'alice12345');
        $other1  = $this->makeDeviceToken(id: 2, memberId: 'alice12345');
        $other2  = $this->makeDeviceToken(id: 3, memberId: 'alice12345');

        $tokenRepo = $this->createMock(DeviceTokenRepository::class);
        $tokenRepo->method('findByMemberId')->with('alice12345')->willReturn([$current, $other1, $other2]);

        $saved = [];
        $tokenRepo->method('save')->willReturnCallback(function (DeviceToken $t) use (&$saved): DeviceToken {
            $saved[] = $t;
            return $t;
        });

        $controller = $this->makeController($tokenRepo);

        $response = $controller->handle($this->makeRequest(
            memberId: 'alice12345',
            deviceId: 1,
            body: ['currentPassword' => 'oldpassword1', 'newPassword' => 'newpassword2'],
        ));

        self::assertSame(204, $response->status);

        // Both other devices should have been saved with revoked=true; the
        // current device should NOT have been touched.
        $revokedIds = [];
        foreach ($saved as $t) {
            self::assertTrue($t->revoked, 'Saved tokens must be revoked.');
            $revokedIds[] = $t->id;
        }
        self::assertContains(2, $revokedIds);
        self::assertContains(3, $revokedIds);
        self::assertNotContains(1, $revokedIds);
    }

    public function testSkipsAlreadyRevokedTokens(): void
    {
        $this->seed('alice12345', 'oldpassword1');

        $current        = $this->makeDeviceToken(id: 1, memberId: 'alice12345');
        $alreadyRevoked = $this->makeDeviceToken(id: 2, memberId: 'alice12345')->revoke();

        $tokenRepo = $this->createMock(DeviceTokenRepository::class);
        $tokenRepo->method('findByMemberId')->with('alice12345')->willReturn([$current, $alreadyRevoked]);

        // No save should be called: current is skipped, other is already revoked.
        $tokenRepo->expects(self::never())->method('save');

        $controller = $this->makeController($tokenRepo);
        $controller->handle($this->makeRequest(
            memberId: 'alice12345',
            deviceId: 1,
            body: ['currentPassword' => 'oldpassword1', 'newPassword' => 'newpassword2'],
        ));
    }

    private function makeController(DeviceTokenRepository $tokenRepo): PasswordChangeController
    {
        return new PasswordChangeController(
            jwtGuard: new JwtGuard($this->jwt),
            credentials: $this->credentials,
            tokens: $tokenRepo,
            rateLimiter: $this->limiter,
        );
    }

    /** @param array<string, mixed> $body */
    private function makeRequest(string $memberId, int $deviceId, array $body): HttpRequest
    {
        $jwt = $this->jwt->issue(
            deviceTokenId: $deviceId,
            memberId: $memberId,
            scopes: DeviceToken::DEFAULT_SCOPES,
        );

        return new HttpRequest(
            method: 'POST',
            path: '/api/v1/auth/password',
            headers: [
                'authorization' => 'Bearer '.$jwt['token'],
                'content-type'  => 'application/json',
            ],
            body: (string) json_encode($body),
        );
    }

    private function makeDeviceToken(int $id, string $memberId): DeviceToken
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return new DeviceToken(
            id: $id,
            tokenHash: hash('sha256', 'seed_'.$id),
            refreshTokenHash: hash('sha256', 'refresh_'.$id),
            deviceName: 'Device '.$id,
            revoked: false,
            lastUsedAt: null,
            expiresAt: $now->modify('+30 days'),
            createdAt: $now,
            memberId: $memberId,
            scopes: DeviceToken::DEFAULT_SCOPES,
        );
    }

    private function seed(string $id, string $rawPassword): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO Member (id, password, userName) VALUES (?, ?, ?)');
        $stmt->execute([$id, Member::hashPassword($rawPassword), 'Test User']);
    }
}
