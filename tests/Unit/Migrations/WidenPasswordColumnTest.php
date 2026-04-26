<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Migrations;

use Phinx\Migration\AbstractMigration;
use Phinx\Migration\IrreversibleMigrationException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Smoke checks for the Phinx migration class itself — class name matches
 * the file slug, extends the right base, and `down()` raises rather than
 * silently dropping. Real schema verification happens against MariaDB in
 * the integration suite (added in M4-C).
 */
final class WidenPasswordColumnTest extends TestCase
{
    private const FILE  = __DIR__.'/../../../migrations/M1/20260101000001_widen_password_column.php';
    private const CLASS_NAME = 'WidenPasswordColumn';

    public function testFileExistsAtTheExpectedPath(): void
    {
        self::assertFileExists(self::FILE);
    }

    public function testClassNameMatchesFileSlug(): void
    {
        require_once self::FILE;

        self::assertTrue(class_exists(self::CLASS_NAME, autoload: false));
    }

    public function testExtendsAbstractMigration(): void
    {
        require_once self::FILE;

        $reflection = new ReflectionClass(self::CLASS_NAME);
        self::assertTrue($reflection->isSubclassOf(AbstractMigration::class));
    }

    public function testIsFinal(): void
    {
        require_once self::FILE;

        self::assertTrue((new ReflectionClass(self::CLASS_NAME))->isFinal());
    }

    public function testDownIsExplicitlyIrreversible(): void
    {
        require_once self::FILE;

        $migration = (new ReflectionClass(self::CLASS_NAME))
            ->newInstanceWithoutConstructor();

        $this->expectException(IrreversibleMigrationException::class);

        $migration->down();
    }
}
