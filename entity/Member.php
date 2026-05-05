<?php

namespace saso\entity;

use saso\util\monad\Either;

final class Member
{
    public function __construct(
        private string $id,
        private string $name,
        private string $password,
        private string $role = 'operator',
        private ?string $avatarUrl = null,
        private ?string $displayName = null,
        private ?string $bio = null,
        private ?\DateTime $updatedAt = null,
    )
    {
    }
    public function __get($name)
    {
        return $this->$name;
    }
    public static function idConstraint(string $id): Either
    {
        return Either::fromNullable(filter_var(
            $id,
            \FILTER_VALIDATE_REGEXP,
            [
                'options'=>[
                    'default'=>false,
                    'regexp'=>'/^[0-9a-zA-Z-_]{8,20}$/'
                ]
            ]
        ));
    }
    public static function nameConstraint(string $name): Either
    {
        return EntityConstraint::requiredStringConstraint($name, 50);
    }
    public static function passwordConstraint(string $password): Either
    {
        return Either::of($password)
            ->filter(fn($v)=>!empty($v))
            ->filter(fn($v)=>mb_strlen($v)<=20&&mb_strlen($v)>=8)
            ->filter(fn($v)=>preg_match('/[^0-9a-zA-Z]/', $v)===0);
    }

    public static function avatarUrlConstraint(?string $url): Either
    {
        if (empty($url)) {
            return Either::of(null);
        }
        return Either::of($url)
            ->filter(fn($v) => filter_var($v, FILTER_VALIDATE_URL) !== false)
            ->filter(fn($v) => preg_match('/\.(jpg|jpeg|png|webp)$/i', $v) === 1);
    }

    public static function displayNameConstraint(?string $name): Either
    {
        if (empty($name)) {
            return Either::of(null);
        }
        return EntityConstraint::requiredStringConstraint($name, 100);
    }

    public static function bioConstraint(?string $bio): Either
    {
        if (empty($bio)) {
            return Either::of(null);
        }
        return Either::of($bio)
            ->filter(fn($v) => mb_strlen($v) <= 500);
    }

    /**
     * Hash a raw password using Argon2id.
     * Stored hashes start with "$argon2id$" and are ~95 chars; the password
     * column must be VARCHAR(255) (see migrations/M1_001_widen_password_column.sql).
     */
    public static function hashPassword(string $raw): string
    {
        return password_hash($raw, PASSWORD_ARGON2ID);
    }

    /**
     * Verify a raw password against a stored hash.
     * Accepts both modern (Argon2id / bcrypt) hashes and the legacy SHA256 chain
     * to support transparent upgrades during the M1 migration window. Callers
     * should follow up successful legacy verifications with hashPassword() and
     * a DB UPDATE; see needsRehash() and LoginUsecase.
     */
    public static function verifyPassword(string $raw, string $storedHash): bool
    {
        if ($storedHash !== '' && str_starts_with($storedHash, '$')) {
            return password_verify($raw, $storedHash);
        }
        return hash_equals(self::legacyHashed($raw), $storedHash);
    }

    /**
     * Whether a stored hash should be re-generated with Argon2id on next login.
     * Returns true for legacy SHA256 chains and any non-Argon2id modern hash.
     */
    public static function needsRehash(string $storedHash): bool
    {
        if ($storedHash === '' || !str_starts_with($storedHash, '$')) {
            return true;
        }
        return password_needs_rehash($storedHash, PASSWORD_ARGON2ID);
    }

    /**
     * Legacy (pre-M1) password hashing: SHA256 + hardcoded global salts iterated 1000x.
     * Kept private and read-only — used solely to verify and upgrade existing
     * Member rows. New writes must go through hashPassword().
     */
    private static function legacyHashed(string $raw): string
    {
        $hashed = hash('sha256', $raw);
        $salted = 'stok-administra_sistemo'.$hashed.'plej_simpla';
        return array_reduce(
            range(1, 1000),
            fn($carry, $item) => hash('sha256', $carry),
            $salted,
        );
    }
}
