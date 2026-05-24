<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Api\V1\Controller\Auth;

use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Application\Auth\IssueTokenPairService;
use Saso\Application\Auth\RateLimiter;
use Saso\Application\Auth\VerifyCredentialsService;
use Saso\Domain\Auth\Exception\InvalidCredentialsException;
use Saso\Domain\Auth\Exception\MalformedRequestException;
use Saso\Domain\Auth\Exception\RateLimitedException;
use Saso\Domain\MobileConnect\DeviceToken;
use Saso\Domain\MobileConnect\Jwt\JwtService;
use Saso\Domain\MobileConnect\Repository\DeviceTokenRepository;
use saso\entity\Member;
use Saso\Presentation\Api\V1\Controller\Auth\LoginController;
use Saso\Presentation\Api\V1\HttpRequest;

final class LoginControllerTest extends TestCase
{
    private const JWT_SECRET = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private PDO $pdo;
    /** @var DeviceTokenRepository&\PHPUnit\Framework\MockObject\MockObject */
    private DeviceTokenRepository $tokenRepo;
    private RateLimiter $limiter;
    private LoginController $controller;
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

        $this->tokenRepo = $this->createMock(DeviceTokenRepository::class);
        $this->tokenRepo->method('nextId')->willReturn(11);
        $this->tokenRepo->method('save')->willReturnCallback(static fn (DeviceToken $t): DeviceToken => $t);

        $this->tempDir = sys_get_temp_dir().'/saso-login-test-'.bin2hex(random_bytes(4));
        $this->limiter = new RateLimiter($this->tempDir, maxAttempts: 3, windowSeconds: 60);

        $this->controller = new LoginController(
            credentials: new VerifyCredentialsService($this->pdo),
            issueTokenPair: new IssueTokenPairService($this->tokenRepo, new JwtService(self::JWT_SECRET)),
            rateLimiter: $this->limiter,
        );
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

    public function testValidCredentialsReturn201WithTokenPair(): void
    {
        $this->seed('alice12345', 'hunter2hunter2');

        $request = $this->makeRequest(['username' => 'alice12345', 'password' => 'hunter2hunter2']);
        $response = $this->controller->handle($request);

        self::assertSame(201, $response->status);
        self::assertSame('Bearer', $response->body['token_type']);
        self::assertSame(11, $response->body['device_id']);
        self::assertIsString($response->body['access_token']);
        self::assertIsString($response->body['refresh_token']);

        // Verify the access token is a real, verifiable JWT.
        $jwt    = new JwtService(self::JWT_SECRET);
        $claims = $jwt->verify($response->body['access_token']);
        self::assertSame(11, $claims->deviceId);
        self::assertSame('alice12345', $claims->memberId);
    }

    public function testDefaultDeviceNameWhenOmitted(): void
    {
        $this->seed('alice12345', 'hunter2hunter2');

        $captured = null;
        $this->tokenRepo = $this->createMock(DeviceTokenRepository::class);
        $this->tokenRepo->method('nextId')->willReturn(1);
        $this->tokenRepo->method('save')->willReturnCallback(function (DeviceToken $t) use (&$captured): DeviceToken {
            $captured = $t;
            return $t;
        });

        $controller = new LoginController(
            credentials: new VerifyCredentialsService($this->pdo),
            issueTokenPair: new IssueTokenPairService($this->tokenRepo, new JwtService(self::JWT_SECRET)),
            rateLimiter: $this->limiter,
        );

        $controller->handle($this->makeRequest(['username' => 'alice12345', 'password' => 'hunter2hunter2']));

        self::assertNotNull($captured);
        self::assertSame('Unknown device', $captured->deviceName);
    }

    public function testWrongPasswordReturns401InvalidCredentials(): void
    {
        $this->seed('alice12345', 'hunter2hunter2');

        $this->expectException(InvalidCredentialsException::class);
        $this->controller->handle($this->makeRequest([
            'username' => 'alice12345',
            'password' => 'wrong-password',
        ]));
    }

    public function testNonExistentUserReturnsSameInvalidCredentials(): void
    {
        // No enumeration leak — same exception as wrong-password.
        $this->expectException(InvalidCredentialsException::class);
        $this->controller->handle($this->makeRequest([
            'username' => 'nobody1234',
            'password' => 'whateverpw1',
        ]));
    }

    public function testMalformedBodyMissingFieldsThrowsMalformedRequest(): void
    {
        $this->expectException(MalformedRequestException::class);
        $this->controller->handle($this->makeRequest(['username' => 'alice12345']));
    }

    public function testMalformedBodyNonStringPasswordThrowsMalformedRequest(): void
    {
        $this->expectException(MalformedRequestException::class);
        $this->controller->handle($this->makeRequest([
            'username' => 'alice12345',
            'password' => ['not', 'a', 'string'],
        ]));
    }

    public function testRateLimitKicksInOnFourthFailedAttempt(): void
    {
        $this->seed('alice12345', 'hunter2hunter2');

        // 3 failed attempts exhaust the bucket (maxAttempts = 3).
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->controller->handle($this->makeRequest([
                    'username' => 'alice12345',
                    'password' => 'wrong-password',
                ]));
                self::fail('Expected InvalidCredentialsException');
            } catch (InvalidCredentialsException) {
                // expected
            }
        }

        // 4th attempt — even with the CORRECT password — must hit the limiter.
        $this->expectException(RateLimitedException::class);
        $this->controller->handle($this->makeRequest([
            'username' => 'alice12345',
            'password' => 'hunter2hunter2',
        ]));
    }

    public function testSuccessfulLoginResetsRateLimitBucket(): void
    {
        $this->seed('alice12345', 'hunter2hunter2');

        // Two failed attempts (still allowed; maxAttempts = 3).
        for ($i = 0; $i < 2; $i++) {
            try {
                $this->controller->handle($this->makeRequest([
                    'username' => 'alice12345',
                    'password' => 'wrong-password',
                ]));
            } catch (InvalidCredentialsException) {
                // expected
            }
        }

        // Successful login.
        $this->controller->handle($this->makeRequest([
            'username' => 'alice12345',
            'password' => 'hunter2hunter2',
        ]));

        // Bucket should be reset → another 3 failures should be possible.
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->controller->handle($this->makeRequest([
                    'username' => 'alice12345',
                    'password' => 'wrong-password',
                ]));
                self::fail('Expected InvalidCredentialsException');
            } catch (InvalidCredentialsException) {
                // expected
            }
        }

        // 4th failure now blocked.
        $this->expectException(RateLimitedException::class);
        $this->controller->handle($this->makeRequest([
            'username' => 'alice12345',
            'password' => 'wrong-password',
        ]));
    }

    public function testPasswordIsNotTrimmed(): void
    {
        // A password that LITERALLY contains a trailing space character. The
        // controller must not silently strip it — that would let an attacker
        // log in as a user whose password happened to start/end with a space
        // by submitting the trimmed variant, and would also surface as "your
        // password works in /change but not in /login" UX bugs.
        //
        // Note: the project's `passwordConstraint` already rejects whitespace
        // in new passwords, so this case only arises with legacy SHA-256
        // chained passwords set before that constraint landed. Either way
        // the login controller must not mutate user-supplied secret material.
        $passwordWithSpace = 'has space ';
        $this->seed('alice12345', $passwordWithSpace);

        // Trimmed variant must NOT authenticate.
        try {
            $this->controller->handle($this->makeRequest([
                'username' => 'alice12345',
                'password' => trim($passwordWithSpace),
            ]));
            self::fail('Expected InvalidCredentialsException for trimmed password');
        } catch (InvalidCredentialsException) {
            // expected
        }

        // The verbatim password must authenticate.
        $response = $this->controller->handle($this->makeRequest([
            'username' => 'alice12345',
            'password' => $passwordWithSpace,
        ]));
        self::assertSame(201, $response->status);
    }

    public function testUsernameIsTrimmed(): void
    {
        // Username, in contrast, IS trimmed — operators occasionally paste
        // ids with surrounding whitespace from terminals or password
        // managers. Trimming the username is safe because the legacy
        // `idConstraint` regex would reject whitespace anyway.
        $this->seed('alice12345', 'hunter2hunter2');

        $response = $this->controller->handle($this->makeRequest([
            'username' => '  alice12345  ',
            'password' => 'hunter2hunter2',
        ]));
        self::assertSame(201, $response->status);
    }

    public function testXForwardedForIsIgnoredForUntrustedProxy(): void
    {
        // When TRUSTED_PROXIES is unset and REMOTE_ADDR is not loopback,
        // a client-supplied X-Forwarded-For must NOT shift the rate-limit
        // bucket. Otherwise an attacker could defeat the per-IP component
        // by rotating fake header values.
        $this->seed('alice12345', 'hunter2hunter2');

        $previousRemote   = $_SERVER['REMOTE_ADDR'] ?? null;
        $previousTrustEnv = getenv('TRUSTED_PROXIES');

        $_SERVER['REMOTE_ADDR'] = '203.0.113.10'; // not loopback, not trusted
        putenv('TRUSTED_PROXIES'); // clear any env that might leak from CI

        try {
            // Exhaust the bucket with 3 failed attempts.
            for ($i = 0; $i < 3; $i++) {
                try {
                    $this->controller->handle($this->makeRequest(
                        body: [
                            'username' => 'alice12345',
                            'password' => 'wrong-password',
                        ],
                        forwardedFor: '198.51.100.'.$i, // fake rotating IPs
                    ));
                    self::fail('Expected InvalidCredentialsException');
                } catch (InvalidCredentialsException) {
                    // expected
                }
            }

            // 4th attempt — even with a yet another fake X-Forwarded-For —
            // must still be blocked, because we keyed the bucket on the real
            // REMOTE_ADDR, not the spoofed header.
            $this->expectException(RateLimitedException::class);
            $this->controller->handle($this->makeRequest(
                body: [
                    'username' => 'alice12345',
                    'password' => 'wrong-password',
                ],
                forwardedFor: '198.51.100.99',
            ));
        } finally {
            if ($previousRemote === null) {
                unset($_SERVER['REMOTE_ADDR']);
            } else {
                $_SERVER['REMOTE_ADDR'] = $previousRemote;
            }
            if ($previousTrustEnv !== false) {
                putenv('TRUSTED_PROXIES='.$previousTrustEnv);
            }
        }
    }

    public function testXForwardedForHonouredFromTrustedProxy(): void
    {
        // When REMOTE_ADDR matches the TRUSTED_PROXIES allowlist, the
        // X-Forwarded-For header is honoured (so the bucket keys on the
        // real client IP, not the reverse-proxy's exit IP).
        $this->seed('alice12345', 'hunter2hunter2');

        $previousRemote   = $_SERVER['REMOTE_ADDR'] ?? null;
        $previousTrustEnv = getenv('TRUSTED_PROXIES');

        $_SERVER['REMOTE_ADDR'] = '10.0.0.5';
        putenv('TRUSTED_PROXIES=10.0.0.5');

        try {
            // Three failures from "client A" via the trusted proxy fill the
            // bucket keyed on the forwarded IP.
            $aThrown = 0;
            for ($i = 0; $i < 3; $i++) {
                try {
                    $this->controller->handle($this->makeRequest(
                        body: [
                            'username' => 'alice12345',
                            'password' => 'wrong-password',
                        ],
                        forwardedFor: '198.51.100.1',
                    ));
                } catch (InvalidCredentialsException) {
                    $aThrown++;
                }
            }
            self::assertSame(3, $aThrown, 'All three attempts from client A should fail with InvalidCredentials.');

            // Client A should now be rate-limited (bucket full).
            try {
                $this->controller->handle($this->makeRequest(
                    body: [
                        'username' => 'alice12345',
                        'password' => 'wrong-password',
                    ],
                    forwardedFor: '198.51.100.1',
                ));
                self::fail('Expected RateLimitedException for client A.');
            } catch (RateLimitedException) {
                // expected
            }

            // "Client B" via the same trusted proxy must still have a fresh
            // bucket — confirming we keyed on the forwarded IP, not the
            // proxy's exit IP. A 4th attempt with a DIFFERENT forwarded IP
            // must produce the credential exception, not a rate-limit one.
            try {
                $this->controller->handle($this->makeRequest(
                    body: [
                        'username' => 'alice12345',
                        'password' => 'wrong-password',
                    ],
                    forwardedFor: '198.51.100.2',
                ));
                self::fail('Expected InvalidCredentialsException for client B.');
            } catch (RateLimitedException) {
                self::fail('Client B was incorrectly rate-limited — bucket was not keyed on the forwarded IP.');
            } catch (InvalidCredentialsException) {
                self::assertTrue(true, 'Client B got its own bucket.');
            }
        } finally {
            if ($previousRemote === null) {
                unset($_SERVER['REMOTE_ADDR']);
            } else {
                $_SERVER['REMOTE_ADDR'] = $previousRemote;
            }
            if ($previousTrustEnv === false) {
                putenv('TRUSTED_PROXIES');
            } else {
                putenv('TRUSTED_PROXIES='.$previousTrustEnv);
            }
        }
    }

    /** @param array<string, mixed> $body */
    private function makeRequest(array $body, ?string $forwardedFor = null): HttpRequest
    {
        $headers = ['content-type' => 'application/json'];
        if ($forwardedFor !== null) {
            $headers['x-forwarded-for'] = $forwardedFor;
        }

        return new HttpRequest(
            method: 'POST',
            path: '/api/v1/auth/login',
            headers: $headers,
            body: (string) json_encode($body),
        );
    }

    private function seed(string $id, string $rawPassword): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO Member (id, password, userName) VALUES (?, ?, ?)');
        $stmt->execute([$id, Member::hashPassword($rawPassword), 'Test User']);
    }
}
