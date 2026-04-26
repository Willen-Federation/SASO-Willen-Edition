<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Migrations;

use Phinx\Migration\AbstractMigration;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Smoke checks for the M4 auth_provider + member_external_identity
 * migrations.
 */
final class CreateAuthProviderTest extends TestCase
{
    private const PROVIDER_FILE  = __DIR__.'/../../../migrations/M4/20260426120002_create_auth_provider.php';
    private const IDENTITY_FILE  = __DIR__.'/../../../migrations/M4/20260426120003_create_member_external_identity.php';
    private const PROVIDER_CLASS = 'CreateAuthProvider';
    private const IDENTITY_CLASS = 'CreateMemberExternalIdentity';

    public function testFilesExist(): void
    {
        self::assertFileExists(self::PROVIDER_FILE);
        self::assertFileExists(self::IDENTITY_FILE);
    }

    public function testClassesMatchSlugs(): void
    {
        require_once self::PROVIDER_FILE;
        require_once self::IDENTITY_FILE;

        self::assertTrue(class_exists(self::PROVIDER_CLASS, autoload: false));
        self::assertTrue(class_exists(self::IDENTITY_CLASS, autoload: false));
    }

    public function testBothExtendAbstractMigration(): void
    {
        require_once self::PROVIDER_FILE;
        require_once self::IDENTITY_FILE;

        self::assertTrue((new ReflectionClass(self::PROVIDER_CLASS))->isSubclassOf(AbstractMigration::class));
        self::assertTrue((new ReflectionClass(self::IDENTITY_CLASS))->isSubclassOf(AbstractMigration::class));
    }

    public function testBothAreFinal(): void
    {
        require_once self::PROVIDER_FILE;
        require_once self::IDENTITY_FILE;

        self::assertTrue((new ReflectionClass(self::PROVIDER_CLASS))->isFinal());
        self::assertTrue((new ReflectionClass(self::IDENTITY_CLASS))->isFinal());
    }
}
