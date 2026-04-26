<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Migrations;

use Phinx\Migration\AbstractMigration;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Smoke checks for the M4 feature_flag, error_log_aggregate, and
 * feature_flag_audit migration classes.
 */
final class CreateFeatureFlagTest extends TestCase
{
    private const FILES = [
        'CreateFeatureFlag'        => __DIR__.'/../../../migrations/M4/20260426120004_create_feature_flag.php',
        'CreateErrorLogAggregate'  => __DIR__.'/../../../migrations/M4/20260426120005_create_error_log_aggregate.php',
        'CreateFeatureFlagAudit'   => __DIR__.'/../../../migrations/M4/20260426120006_create_feature_flag_audit.php',
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
