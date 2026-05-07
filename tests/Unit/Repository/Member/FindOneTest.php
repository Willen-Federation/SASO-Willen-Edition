<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Repository\Member;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use saso\repository\member\FindOne;

#[CoversClass(FindOne::class)]
final class FindOneTest extends TestCase
{
    public function testQuerySelectsProfileFieldsPersistedByMyPageEditing(): void
    {
        $query = (new FindOne())->getQuery();

        self::assertStringContainsString('avatar_url', $query);
        self::assertStringContainsString('display_name', $query);
        self::assertStringContainsString('bio', $query);
        self::assertStringContainsString('updated_at', $query);
        self::assertStringNotContainsString('NULL AS avatar_url', $query);
    }
}
