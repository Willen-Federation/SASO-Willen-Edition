<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Migrations;

use Phinx\Migration\AbstractMigration;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Smoke checks for the M4 system_setting + system_setting_audit
 * migration classes: file paths exist, class names match the slug,
 * and both extend Phinx's AbstractMigration. Real schema verification
 * happens against MariaDB in the integration suite.
 */
final class CreateSystemSettingTest extends TestCase
{
    private const SETTING_FILE  = __DIR__.'/../../../migrations/M4/20260426120000_create_system_setting.php';
    private const AUDIT_FILE    = __DIR__.'/../../../migrations/M4/20260426120001_create_system_setting_audit.php';
    private const SETTING_CLASS = 'CreateSystemSetting';
    private const AUDIT_CLASS   = 'CreateSystemSettingAudit';

    public function testBothFilesExist(): void
    {
        self::assertFileExists(self::SETTING_FILE);
        self::assertFileExists(self::AUDIT_FILE);
    }

    public function testClassNamesMatchFileSlugs(): void
    {
        require_once self::SETTING_FILE;
        require_once self::AUDIT_FILE;

        self::assertTrue(class_exists(self::SETTING_CLASS, autoload: false));
        self::assertTrue(class_exists(self::AUDIT_CLASS, autoload: false));
    }

    public function testBothExtendAbstractMigration(): void
    {
        require_once self::SETTING_FILE;
        require_once self::AUDIT_FILE;

        self::assertTrue((new ReflectionClass(self::SETTING_CLASS))->isSubclassOf(AbstractMigration::class));
        self::assertTrue((new ReflectionClass(self::AUDIT_CLASS))->isSubclassOf(AbstractMigration::class));
    }

    public function testBothAreFinal(): void
    {
        require_once self::SETTING_FILE;
        require_once self::AUDIT_FILE;

        self::assertTrue((new ReflectionClass(self::SETTING_CLASS))->isFinal());
        self::assertTrue((new ReflectionClass(self::AUDIT_CLASS))->isFinal());
    }
}
