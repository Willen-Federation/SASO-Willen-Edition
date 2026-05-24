<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Application\Auth;

use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use ReflectionClass;
use Saso\Application\Auth\LoginOrchestrator;
use Saso\Domain\Auth\AuthenticatedIdentity;
use Saso\Domain\Auth\AuthProviderId;
use Saso\Domain\Auth\Exception\AuthFailedException;
use Saso\Domain\Auth\ExternalIdentity;
use Saso\Domain\Auth\Repository\ExternalIdentityRepository;
use Saso\Infrastructure\Auth\AuthProviderFactory;
use Stringable;

/**
 * Focused tests for the silent-fallback paths the audit flagged:
 *
 *   - JIT member provisioning failure must throw a generic
 *     {@see AuthFailedException} AND log the underlying DB cause so the
 *     operator can see what actually broke.
 *
 *   - {@see LoginOrchestrator::findLocalMemberIdByEmail()} swallows
 *     schema-mismatch errors by design (the legacy Member table has no
 *     email column on most installs). The catch path must still log so a
 *     brand-new exception text surfaces in monitoring.
 *
 * Because `AuthProviderFactory` is `final` we cannot mock it with the
 * PHPUnit double generator, and the JIT and email-lookup helpers are
 * private methods on the orchestrator. We use reflection to invoke them
 * directly — that lets us assert on the *new* logger side effect without
 * having to also wire up the entire provider machinery.
 */
final class LoginOrchestratorTest extends TestCase
{
    private PDO $pdo;
    private LoginOrchestratorTestLogger $logger;
    private LoginOrchestrator $orchestrator;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(
            'CREATE TABLE Member (
                id       VARCHAR(20) PRIMARY KEY,
                userName VARCHAR(50) NOT NULL,
                password VARCHAR(255) NOT NULL
            )',
        );
        $this->logger = new LoginOrchestratorTestLogger();

        // The factory is final and unused for these private-method tests;
        // pass a real but never-invoked instance. We use ReflectionClass
        // to skip its constructor since the only fields we touch are
        // the orchestrator's own.
        $factory = (new ReflectionClass(AuthProviderFactory::class))->newInstanceWithoutConstructor();

        $this->orchestrator = new LoginOrchestrator(
            providers:          $factory,
            externalIdentities: new InMemoryExternalIdentityRepository(),
            pdo:                $this->pdo,
            logger:             $this->logger,
        );
    }

    public function testJitInsertFailureIsLoggedAndRethrownAsAuthFailure(): void
    {
        // Recreate the Member table without the userName/password columns
        // so the JIT INSERT will fail at the DB layer.
        $this->pdo->exec('DROP TABLE Member');
        $this->pdo->exec('CREATE TABLE Member (id VARCHAR(20) PRIMARY KEY)');

        $identity = new AuthenticatedIdentity(
            authProviderId:  new AuthProviderId(1),
            externalSubject: 'sub-abc',
            email:           'alice@example.com',
            displayName:     'Alice',
        );

        $createJit = (new ReflectionClass($this->orchestrator))->getMethod('createJitMember');
        $createJit->setAccessible(true);

        $caught = null;
        try {
            $createJit->invoke($this->orchestrator, $identity);
        } catch (\Throwable $e) {
            $caught = $e;
        }

        self::assertInstanceOf(
            AuthFailedException::class,
            $caught,
            'JIT failure must re-throw as a generic AuthFailedException.',
        );

        $errorRecord = $this->findRecord('error');
        self::assertNotNull($errorRecord, 'JIT failure must emit an error-level log line.');
        self::assertStringContainsString('JIT member provisioning failed', $errorRecord['message']);
        self::assertArrayHasKey('candidate', $errorRecord['context']);
        self::assertArrayHasKey('error', $errorRecord['context']);
    }

    public function testEmailLookupSchemaMismatchIsLoggedAtInfoLevel(): void
    {
        // Drop the Member table entirely so the SELECT throws.
        $this->pdo->exec('DROP TABLE Member');

        $find = (new ReflectionClass($this->orchestrator))->getMethod('findLocalMemberIdByEmail');
        $find->setAccessible(true);

        $result = $find->invoke($this->orchestrator, 'alice@example.com');

        self::assertNull($result, 'Schema mismatch must return null (fall through to JIT).');
        $infoRecord = $this->findRecord('info');
        self::assertNotNull($infoRecord, 'Schema mismatch must be logged at info level.');
        self::assertStringContainsString(
            'local member lookup by email skipped',
            $infoRecord['message'],
        );
    }

    public function testEmailLookupReturnsNullWithoutLoggingWhenEmailIsEmpty(): void
    {
        // Empty email never even reaches the DB; logger must stay quiet.
        $find = (new ReflectionClass($this->orchestrator))->getMethod('findLocalMemberIdByEmail');
        $find->setAccessible(true);

        $result = $find->invoke($this->orchestrator, '');
        self::assertNull($result);
        self::assertSame([], $this->logger->records);
    }

    public function testDefaultLoggerIsNullLoggerSoOldCallSitesStillCompile(): void
    {
        // Source compatibility: a three-arg constructor must keep working.
        $factory = (new ReflectionClass(AuthProviderFactory::class))->newInstanceWithoutConstructor();

        $orchestrator = new LoginOrchestrator(
            providers:          $factory,
            externalIdentities: new InMemoryExternalIdentityRepository(),
            pdo:                $this->pdo,
        );
        self::assertInstanceOf(LoginOrchestrator::class, $orchestrator);
    }

    /**
     * @return array{level: string, message: string, context: array<string, mixed>}|null
     */
    private function findRecord(string $level): ?array
    {
        foreach ($this->logger->records as $record) {
            if ($record['level'] === $level) {
                return $record;
            }
        }
        return null;
    }
}

/**
 * In-memory ExternalIdentityRepository for tests — never returns an
 * existing identity (so JIT provisioning is always exercised).
 */
final class InMemoryExternalIdentityRepository implements ExternalIdentityRepository
{
    public function find(AuthProviderId $providerId, string $externalSubject): ?ExternalIdentity
    {
        return null;
    }

    public function listForMember(string $memberId): array
    {
        return [];
    }

    public function link(ExternalIdentity $identity): void
    {
        // no-op
    }

    public function recordLogin(AuthProviderId $providerId, string $externalSubject): void
    {
        // no-op
    }

    public function unlink(AuthProviderId $providerId, string $externalSubject): void
    {
        // no-op
    }
}

/**
 * PSR-3 logger that captures records for assertions.
 */
final class LoginOrchestratorTestLogger extends AbstractLogger
{
    /** @var list<array{level: string, message: string, context: array<string, mixed>}> */
    public array $records = [];

    /**
     * @param mixed $level
     * @param string|Stringable $message
     * @param array<mixed> $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->records[] = [
            'level'   => (string) $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}
