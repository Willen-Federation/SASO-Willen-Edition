<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Migrations;

use Phinx\Migration\AbstractMigration;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Smoke checks for the M6 plugin_registry migration class.
 */
final class CreatePluginRegistryTest extends TestCase
{
    private const FILE  = __DIR__.'/../../../migrations/M6/20260427120000_create_plugin_registry.php';
    private const CLASS_NAME = 'CreatePluginRegistry';

    public function testFileExists(): void
    {
        self::assertFileExists(self::FILE);
    }

    public function testClassMatchesSlug(): void
    {
        require_once self::FILE;

        self::assertTrue(class_exists(self::CLASS_NAME, autoload: false));
    }

    public function testExtendsAbstractMigration(): void
    {
        require_once self::FILE;

        self::assertTrue(
            (new ReflectionClass(self::CLASS_NAME))->isSubclassOf(AbstractMigration::class),
        );
    }

    public function testIsFinal(): void
    {
        require_once self::FILE;

        self::assertTrue((new ReflectionClass(self::CLASS_NAME))->isFinal());
    }
}
