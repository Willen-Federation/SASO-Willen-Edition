<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Migrations;

use Phinx\Migration\AbstractMigration;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Smoke checks for the M6 storage_location + similar_item migration
 * classes.
 */
final class CreateStorageLocationTest extends TestCase
{
    private const FILES = [
        'CreateStorageLocation' => __DIR__.'/../../../migrations/M6/20260427120001_create_storage_location.php',
        'CreateSimilarItem'     => __DIR__.'/../../../migrations/M6/20260427120002_create_similar_item.php',
    ];

    public function testFilesExist(): void
    {
        foreach (self::FILES as $file) {
            self::assertFileExists($file);
        }
    }

    public function testClassesMatchSlugs(): void
    {
        foreach (self::FILES as $class => $file) {
            require_once $file;
            self::assertTrue(class_exists($class, autoload: false), $class);
        }
    }

    public function testAllExtendAbstractMigration(): void
    {
        foreach (self::FILES as $class => $file) {
            require_once $file;
            self::assertTrue(
                (new ReflectionClass($class))->isSubclassOf(AbstractMigration::class),
                $class,
            );
        }
    }

    public function testAllAreFinal(): void
    {
        foreach (self::FILES as $class => $file) {
            require_once $file;
            self::assertTrue((new ReflectionClass($class))->isFinal(), $class);
        }
    }
}
