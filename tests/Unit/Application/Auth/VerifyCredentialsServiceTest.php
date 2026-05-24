<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Application\Auth;

use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Saso\Application\Auth\VerifyCredentialsService;
use Saso\Domain\Auth\Exception\InvalidCredentialsException;
use saso\entity\Member;
use Stringable;

/**
 * Uses an in-memory SQLite database with a Member table schema-compatible
 * with the production MySQL one so we can exercise the verify() and
 * updatePasswordHash() helpers without a live MySQL connection.
 */
final class VerifyCredentialsServiceTest extends TestCase
{
    private PDO $pdo;
    private VerifyCredentialsService $service;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(
            'CREATE TABLE Member (
                id       VARCHAR(20) NOT NULL PRIMARY KEY,
                password VARCHAR(255) NOT NULL,
                userName VARCHAR(50)  NOT NULL
            )',
        );

        $this->service = new VerifyCredentialsService($this->pdo);
    }

    public function testVerifyReturnsMemberOnValidCredentials(): void
    {
        $this->seed('alice12345', 'hunter2hunter2', 'Alice');

        $result = $this->service->verify('alice12345', 'hunter2hunter2');

        self::assertSame('alice12345', $result['id']);
        self::assertSame('Alice', $result['name']);
    }

    public function testVerifyThrowsOnWrongPassword(): void
    {
        $this->seed('alice12345', 'hunter2hunter2', 'Alice');

        $this->expectException(InvalidCredentialsException::class);
        $this->service->verify('alice12345', 'wrong-password');
    }

    public function testVerifyThrowsOnUnknownUser(): void
    {
        $this->expectException(InvalidCredentialsException::class);
        $this->service->verify('nobody1234', 'whatever123');
    }

    public function testVerifyThrowsOnMalformedUsername(): void
    {
        // Username shorter than 8 chars trips the id constraint.
        $this->expectException(InvalidCredentialsException::class);
        $this->service->verify('a', 'whatever123');
    }

    public function testVerifyRehashesLegacySha256HashOnSuccess(): void
    {
        $raw = 'legacypass12';
        // Reproduce the legacy hash chain exactly (1000 SHA256 rounds with the
        // hardcoded salts), as the Member::verifyPassword path expects.
        $hashed = hash('sha256', $raw);
        $salted = 'stok-administra_sistemo'.$hashed.'plej_simpla';
        $legacy = array_reduce(
            range(1, 1000),
            static fn ($carry, $_) => hash('sha256', $carry),
            $salted,
        );

        $this->pdo->exec("INSERT INTO Member (id, password, userName) VALUES ('alice12345', ".$this->pdo->quote($legacy).", 'Alice')");

        $this->service->verify('alice12345', $raw);

        $stored = (string) $this->pdo->query("SELECT password FROM Member WHERE id = 'alice12345'")->fetchColumn();
        self::assertStringStartsWith('$argon2id$', $stored);
        self::assertTrue(Member::verifyPassword($raw, $stored));
    }

    public function testUpdatePasswordHashWritesNewArgon2Digest(): void
    {
        $this->seed('alice12345', 'oldpassword1', 'Alice');

        $this->service->updatePasswordHash('alice12345', 'newpassword2');

        $stored = (string) $this->pdo->query("SELECT password FROM Member WHERE id = 'alice12345'")->fetchColumn();
        self::assertTrue(Member::verifyPassword('newpassword2', $stored));
        self::assertFalse(Member::verifyPassword('oldpassword1', $stored));
    }

    public function testUpdatePasswordHashThrowsWhenMemberMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->service->updatePasswordHash('nobody', 'newpassword2');
    }

    public function testRehashUpgradeFailureIsLoggedButLoginStillSucceeds(): void
    {
        // Seed with a legacy SHA-256 hash so needsRehash() is true.
        $raw    = 'legacypass12';
        $hashed = hash('sha256', $raw);
        $salted = 'stok-administra_sistemo'.$hashed.'plej_simpla';
        $legacy = array_reduce(
            range(1, 1000),
            static fn ($carry, $_) => hash('sha256', $carry),
            $salted,
        );
        $this->pdo->exec("INSERT INTO Member (id, password, userName) VALUES ('alice12345', ".$this->pdo->quote($legacy).", 'Alice')");

        // Wrap the PDO so SELECT still works (the loadRow lookup) but the
        // rehash UPDATE explodes — exactly the transient-DB-error
        // condition the swallow/log path defends against.
        $logger    = new VerifyTestLogger();
        $brokenPdo = new BrokenUpdatePdo($this->pdo);
        $svc       = new VerifyCredentialsService($brokenPdo, $logger);

        // verify() must still succeed despite the swallowed UPDATE error.
        $result = $svc->verify('alice12345', $raw);
        self::assertSame('alice12345', $result['id']);

        // The swallowed exception must be visible in logs.
        self::assertNotEmpty($logger->records, 'Rehash UPDATE failure must be logged.');
        self::assertSame('warning', $logger->records[0]['level']);
        self::assertStringContainsString(
            'rehash UPDATE failed',
            $logger->records[0]['message'],
        );
        // Critical: the raw password must never appear in the log context.
        self::assertArrayNotHasKey('password', $logger->records[0]['context']);
        $contextString = (string) json_encode($logger->records[0]['context']);
        self::assertStringNotContainsString($raw, $contextString);
    }

    public function testDefaultLoggerIsNullLoggerSoSilentCallSitesStillCompile(): void
    {
        // Source compatibility: existing call sites use
        // new VerifyCredentialsService($pdo) with no logger arg.
        $svc = new VerifyCredentialsService($this->pdo);
        $this->seed('alice12345', 'hunter2hunter2', 'Alice');
        self::assertSame('alice12345', $svc->verify('alice12345', 'hunter2hunter2')['id']);
    }

    private function seed(string $id, string $rawPassword, string $name): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO Member (id, password, userName) VALUES (?, ?, ?)');
        $stmt->execute([$id, Member::hashPassword($rawPassword), $name]);
    }
}

/**
 * PSR-3 logger that captures records for assertions.
 */
final class VerifyTestLogger extends AbstractLogger
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

/**
 * PDO proxy that delegates SELECT statements to the wrapped instance but
 * throws PDOException on prepare() for UPDATE statements. Used to model a
 * transient DB error during the rehash step without actually breaking the
 * preceding SELECT.
 */
final class BrokenUpdatePdo extends PDO
{
    public function __construct(private readonly PDO $inner)
    {
        // Intentionally skip parent::__construct — this proxy never opens
        // its own connection, it only forwards to $inner.
    }

    /**
     * @param array<int, mixed> $options PDO driver-specific prepare options
     */
    public function prepare(string $query, array $options = []): \PDOStatement|false
    {
        if (stripos(ltrim($query), 'UPDATE') === 0) {
            throw new \PDOException('simulated DB error during rehash UPDATE');
        }
        return $this->inner->prepare($query, $options);
    }
}
