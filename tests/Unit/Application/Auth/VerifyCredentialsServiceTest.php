<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Application\Auth;

use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Application\Auth\VerifyCredentialsService;
use Saso\Domain\Auth\Exception\InvalidCredentialsException;
use saso\entity\Member;

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

    private function seed(string $id, string $rawPassword, string $name): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO Member (id, password, userName) VALUES (?, ?, ?)');
        $stmt->execute([$id, Member::hashPassword($rawPassword), $name]);
    }
}
