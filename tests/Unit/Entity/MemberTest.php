<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use saso\entity\Member;

#[CoversClass(Member::class)]
final class MemberTest extends TestCase
{
    public function testHashPasswordProducesArgon2idDigest(): void
    {
        $hash = Member::hashPassword('correct horse battery');
        self::assertStringStartsWith('$argon2id$', $hash);
    }

    public function testHashPasswordIsNonDeterministic(): void
    {
        // Argon2id includes a random salt, so identical inputs must produce
        // distinct digests. This is the property that fixes the legacy
        // "shared global salt → identical digest for identical password"
        // weakness that motivated the M1 migration.
        $a = Member::hashPassword('same-password');
        $b = Member::hashPassword('same-password');
        self::assertNotSame($a, $b);
    }

    public function testVerifyPasswordAcceptsArgon2idMatch(): void
    {
        $hash = Member::hashPassword('correct horse battery');
        self::assertTrue(Member::verifyPassword('correct horse battery', $hash));
    }

    public function testVerifyPasswordRejectsArgon2idMismatch(): void
    {
        $hash = Member::hashPassword('correct horse battery');
        self::assertFalse(Member::verifyPassword('wrong password', $hash));
    }

    public function testVerifyPasswordAcceptsLegacySha256Chain(): void
    {
        // Reproduce the pre-M1 hashing inline so the test remains self-
        // contained and does not depend on a private method via reflection.
        $legacy = self::legacyHash('migrate-me');
        self::assertTrue(Member::verifyPassword('migrate-me', $legacy));
    }

    public function testVerifyPasswordRejectsLegacyMismatch(): void
    {
        $legacy = self::legacyHash('migrate-me');
        self::assertFalse(Member::verifyPassword('migrate-you', $legacy));
    }

    public function testNeedsRehashIsTrueForLegacyDigest(): void
    {
        $legacy = self::legacyHash('any');
        self::assertTrue(Member::needsRehash($legacy));
    }

    public function testNeedsRehashIsTrueForEmptyOrUnknown(): void
    {
        self::assertTrue(Member::needsRehash(''));
        self::assertTrue(Member::needsRehash('not-a-hash'));
    }

    public function testNeedsRehashIsFalseForFreshArgon2idDigest(): void
    {
        $fresh = Member::hashPassword('hi');
        self::assertFalse(Member::needsRehash($fresh));
    }

    public function testIdConstraintAcceptsValidLoginIds(): void
    {
        $either = Member::idConstraint('alice_99');
        self::assertSame('alice_99', $either->getOrElse(null));
    }

    public function testIdConstraintRejectsInvalidLoginIds(): void
    {
        $shortId = Member::idConstraint('abc');
        $longId = Member::idConstraint(str_repeat('x', 21));
        $badChars = Member::idConstraint('alice@home');

        self::assertFalse($shortId->getOrElse(false));
        self::assertFalse($longId->getOrElse(false));
        self::assertFalse($badChars->getOrElse(false));
    }

    public function testPasswordConstraintAcceptsAlphanumericInRange(): void
    {
        $either = Member::passwordConstraint('Aa1Bb2Cc3');
        self::assertSame('Aa1Bb2Cc3', $either->getOrElse(null));
    }

    public function testPasswordConstraintRejectsTooShortOrTooLong(): void
    {
        $short = Member::passwordConstraint('abc1234');
        $long = Member::passwordConstraint(str_repeat('a', 21));

        self::assertNull($short->getOrElse(null));
        self::assertNull($long->getOrElse(null));
    }

    public function testPasswordConstraintRejectsNonAlphanumeric(): void
    {
        $bad = Member::passwordConstraint('passwd!!');
        self::assertNull($bad->getOrElse(null));
    }

    /**
     * Reproduces entity\Member::legacyHashed (private) so tests can construct
     * legacy-format digests without reflection. Mirrors the exact SHA256 chain
     * used pre-M1 — DO NOT change the salts; legacy production rows depend on
     * these constants.
     */
    private static function legacyHash(string $raw): string
    {
        $hashed = hash('sha256', $raw);
        $salted = 'stok-administra_sistemo'.$hashed.'plej_simpla';
        return array_reduce(
            range(1, 1000),
            static fn ($carry, $item) => hash('sha256', (string) $carry),
            $salted,
        );
    }
}
