<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Presentation\Mcp\Tool;

use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use Saso\Domain\Feature\Repository\FeatureFlagRepository;
use Saso\Domain\ItemDraft\Repository\ItemDraftRepository;
use Saso\Domain\Setting\SystemSettingService;
use Saso\Presentation\Mcp\Tool\AutoRegisterItemTool;

/**
 * Covers the early-input-validation branch of {@see AutoRegisterItemTool}.
 *
 * The downstream pipeline is intentionally out of scope; the test only
 * exercises argument validation that runs before the draft is created
 * or the bus is dispatched.
 */
final class AutoRegisterItemToolTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function rejectedPaths(): iterable
    {
        yield 'empty string'        => [''];
        yield 'whitespace only'     => ['   '];
        yield 'absolute unix path'  => ['/etc/passwd'];
        yield 'traversal at start'  => ['../etc/passwd'];
        yield 'traversal mid-path'  => ['uploads/../../../etc/passwd'];
        yield 'traversal at end'    => ['uploads/foo/..'];
        yield 'null byte injection' => ["uploads/ok.jpg\0../../etc/passwd"];
        yield 'windows drive path'  => ['C:\\Windows\\System32\\config\\sam'];
        yield 'windows unc path'    => ['\\\\server\\share\\foo'];
        yield 'backslash traversal' => ['..\\..\\etc\\passwd'];
        yield 'mixed-slash escape'  => ['uploads\\..\\..\\secret.jpg'];
    }

    /** @dataProvider rejectedPaths */
    public function testInvalidImagePathIsRejected(string $imagePath): void
    {
        $tool = $this->makeTool();

        $this->expectException(InvalidArgumentException::class);

        $tool->invoke(['imagePath' => $imagePath], deviceId: 1);
    }

    public function testRequiresImagePath(): void
    {
        $tool = $this->makeTool();

        $this->expectException(InvalidArgumentException::class);

        $tool->invoke([], deviceId: 1);
    }

    private function makeTool(): AutoRegisterItemTool
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // The downstream pipeline is never reached because the validation
        // assertions throw first — the deps just need to satisfy the type
        // constraints of the constructor signature.
        $drafts = $this->createMock(ItemDraftRepository::class);
        $drafts->expects(self::never())->method('create');

        $settings = $this->createMock(SystemSettingService::class);
        $flags    = $this->createMock(FeatureFlagRepository::class);

        return new AutoRegisterItemTool($pdo, $drafts, $settings, $flags);
    }
}
