<?php

declare(strict_types=1);

namespace Saso\Tests\Integration\Auth;

use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Application\Auth\IssueTokenPairService;
use Saso\Application\Auth\RateLimiter;
use Saso\Application\Auth\VerifyCredentialsService;
use Saso\Application\Mobile\JwtGuard;
use Saso\Domain\MobileConnect\DeviceToken;
use Saso\Domain\MobileConnect\Jwt\JwtService;
use Saso\Domain\MobileConnect\Repository\DeviceTokenRepository;
use saso\entity\Member;
use Saso\Presentation\Api\V1\Controller\Auth\LoginController;
use Saso\Presentation\Api\V1\Controller\Auth\LogoutController;
use Saso\Presentation\Api\V1\Controller\Auth\PasswordChangeController;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\OpenApiSpec;
use Saso\Presentation\Api\V1\Router;
use Saso\Presentation\Http\Problem\ProblemExceptionHandler;
use Saso\Presentation\Http\Problem\ProblemRenderer;

/**
 * Exercises the three new `/api/v1/auth/*` endpoints through the
 * schema-first router (the same Router that `Bootstrap::dispatch()`
 * wires in production). Verifies the OpenAPI spec dispatches to the
 * correct handlers and the responses come out in the expected shape.
 *
 * The test does NOT hit MySQL — it uses an in-memory SQLite database
 * for the Member rows and a fake in-memory device-token repository.
 * That matches the testing strategy used by every other API controller
 * suite in this codebase.
 */
final class AuthEndpointsIntegrationTest extends TestCase
{
    private const JWT_SECRET = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private PDO $pdo;
    private JwtService $jwt;
    private VerifyCredentialsService $credentials;
    private InMemoryDeviceTokenRepository $tokenRepo;
    private RateLimiter $limiter;
    private Router $router;
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
        $this->seed('alice12345', 'hunter2hunter2');

        $this->jwt         = new JwtService(self::JWT_SECRET);
        $this->credentials = new VerifyCredentialsService($this->pdo);
        $this->tokenRepo   = new InMemoryDeviceTokenRepository();

        $this->tempDir = sys_get_temp_dir().'/saso-auth-integration-'.bin2hex(random_bytes(4));
        $this->limiter = new RateLimiter($this->tempDir, maxAttempts: 5, windowSeconds: 60);

        $issueTokenPair = new IssueTokenPairService($this->tokenRepo, $this->jwt);
        $jwtGuard       = new JwtGuard($this->jwt);

        $login    = new LoginController($this->credentials, $issueTokenPair, $this->limiter);
        $logout   = new LogoutController($jwtGuard, $this->tokenRepo);
        $password = new PasswordChangeController(
            jwtGuard: $jwtGuard,
            credentials: $this->credentials,
            tokens: $this->tokenRepo,
            rateLimiter: $this->limiter,
        );

        $spec = OpenApiSpec::load(dirname(__DIR__, 3).'/config/openapi.yaml');

        // Build a full handler map: every operationId in the spec must map
        // to *something*, but for the unrelated ones we install no-op
        // closures. The router validates coverage at construction time.
        $handlers = [
            'authLogin'          => [$login, 'handle'],
            'authLogout'         => [$logout, 'handle'],
            'authChangePassword' => [$password, 'handle'],
        ];

        $noop = static fn (HttpRequest $request): \Saso\Presentation\Api\V1\Response\JsonResponse
            => new \Saso\Presentation\Api\V1\Response\JsonResponse(status: 200, body: []);
        foreach ($spec->routes() as $route) {
            if (!array_key_exists($route->operationId, $handlers)) {
                $handlers[$route->operationId] = $noop;
            }
        }

        $exceptionHandler = new ProblemExceptionHandler(
            logger: new \Psr\Log\NullLogger(),
            renderer: new ProblemRenderer(),
            debug: true,
        );

        $this->router = new Router($spec, $handlers, $exceptionHandler);
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

    public function testSpecDeclaresAllThreeAuthOperations(): void
    {
        $spec = OpenApiSpec::load(dirname(__DIR__, 3).'/config/openapi.yaml');
        $ops  = array_map(static fn ($route) => $route->operationId, $spec->routes());

        self::assertContains('authLogin', $ops);
        self::assertContains('authLogout', $ops);
        self::assertContains('authChangePassword', $ops);
    }

    public function testFullLoginLogoutCycle(): void
    {
        $loginResp = $this->dispatch(
            'POST',
            '/api/v1/auth/login',
            ['username' => 'alice12345', 'password' => 'hunter2hunter2'],
        );
        self::assertSame(201, $loginResp['status']);
        self::assertArrayHasKey('access_token', $loginResp['body']);
        self::assertSame('Bearer', $loginResp['body']['token_type']);
        self::assertCount(1, $this->tokenRepo->saved());

        $accessToken = $loginResp['body']['access_token'];

        // Logout via the access token.
        $logoutResp = $this->dispatch(
            'POST',
            '/api/v1/auth/logout',
            body: null,
            authorization: 'Bearer '.$accessToken,
        );
        self::assertSame(204, $logoutResp['status']);

        // Token row is now revoked.
        $rows = $this->tokenRepo->saved();
        self::assertTrue(end($rows)->revoked);

        // Calling logout again is idempotent.
        $logoutAgain = $this->dispatch(
            'POST',
            '/api/v1/auth/logout',
            body: null,
            authorization: 'Bearer '.$accessToken,
        );
        self::assertSame(204, $logoutAgain['status']);
    }

    public function testPasswordChangeFlow(): void
    {
        // Step 1 — login as alice.
        $loginResp = $this->dispatch(
            'POST',
            '/api/v1/auth/login',
            ['username' => 'alice12345', 'password' => 'hunter2hunter2'],
        );
        $accessToken = $loginResp['body']['access_token'];

        // Step 2 — change password.
        $changeResp = $this->dispatch(
            'POST',
            '/api/v1/auth/password',
            ['currentPassword' => 'hunter2hunter2', 'newPassword' => 'brandnewpw1'],
            authorization: 'Bearer '.$accessToken,
        );
        self::assertSame(204, $changeResp['status']);

        // Step 3 — new password works.
        $newLogin = $this->dispatch(
            'POST',
            '/api/v1/auth/login',
            ['username' => 'alice12345', 'password' => 'brandnewpw1'],
        );
        self::assertSame(201, $newLogin['status']);

        // Step 4 — old password is rejected.
        $oldLogin = $this->dispatch(
            'POST',
            '/api/v1/auth/login',
            ['username' => 'alice12345', 'password' => 'hunter2hunter2'],
        );
        self::assertSame(401, $oldLogin['status']);
        self::assertSame('SASO-AUTH-1001', $oldLogin['body']['code']);
    }

    public function testInvalidCredentialsReturn401WithProblemJsonShape(): void
    {
        $resp = $this->dispatch(
            'POST',
            '/api/v1/auth/login',
            ['username' => 'alice12345', 'password' => 'wrong-password'],
        );

        self::assertSame(401, $resp['status']);
        self::assertSame('SASO-AUTH-1001', $resp['body']['code']);
        self::assertArrayHasKey('traceId', $resp['body']);
        self::assertArrayHasKey('type', $resp['body']);
        // The Content-Type header is set by ProblemRenderer::emit() but the
        // CLI test runner does not surface headers through headers_list(),
        // so we assert the body shape (which is the same wire contract).
        self::assertSame(401, $resp['body']['status']);
    }

    /**
     * Dispatch a request through the real Router, captures the rendered
     * response, returns the parsed body and Content-Type header.
     *
     * @param array<string, mixed>|null $body
     *
     * @return array{status: int, body: array<string, mixed>, contentType: string}
     */
    private function dispatch(
        string $method,
        string $path,
        ?array $body = null,
        ?string $authorization = null,
    ): array {
        $headers = ['content-type' => 'application/json'];
        if ($authorization !== null) {
            $headers['authorization'] = $authorization;
        }

        $request = new HttpRequest(
            method: $method,
            path: $path,
            headers: $headers,
            body: $body === null ? null : (string) json_encode($body),
        );

        ob_start();
        $this->router->dispatch($request);
        $output = (string) ob_get_clean();

        $status      = http_response_code();
        $contentType = '';
        foreach (headers_list() as $line) {
            if (stripos($line, 'Content-Type:') === 0) {
                $contentType = trim(substr($line, strlen('Content-Type:')));
            }
        }
        header_remove();

        $parsed = json_decode($output, associative: true);

        return [
            'status'      => is_int($status) ? $status : 200,
            'body'        => is_array($parsed) ? $parsed : [],
            'contentType' => $contentType,
        ];
    }

    private function seed(string $id, string $rawPassword): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO Member (id, password, userName) VALUES (?, ?, ?)');
        $stmt->execute([$id, Member::hashPassword($rawPassword), 'Test User']);
    }
}

/**
 * Minimal in-memory DeviceTokenRepository for the integration test —
 * persists tokens between calls in a single-test lifetime without
 * hitting MySQL.
 */
final class InMemoryDeviceTokenRepository implements DeviceTokenRepository
{
    /** @var array<int, DeviceToken> */
    private array $byId = [];
    private int $nextId = 0;

    public function findByTokenHash(string $hash): ?DeviceToken
    {
        foreach ($this->byId as $token) {
            if ($token->tokenHash === $hash) {
                return $token;
            }
        }
        return null;
    }

    public function findByRefreshTokenHash(string $hash): ?DeviceToken
    {
        foreach ($this->byId as $token) {
            if ($token->refreshTokenHash === $hash) {
                return $token;
            }
        }
        return null;
    }

    public function findById(int $id): ?DeviceToken
    {
        return $this->byId[$id] ?? null;
    }

    public function listAll(): array
    {
        return array_values($this->byId);
    }

    public function findByMemberId(string $memberId): array
    {
        return array_values(array_filter(
            $this->byId,
            static fn (DeviceToken $t): bool => $t->memberId === $memberId,
        ));
    }

    public function nextId(): int
    {
        return ++$this->nextId;
    }

    public function save(DeviceToken $token): DeviceToken
    {
        $this->byId[$token->id] = $token;
        return $token;
    }

    /** @return list<DeviceToken> */
    public function saved(): array
    {
        return array_values($this->byId);
    }
}
