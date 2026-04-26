<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Domain\StorageLocation;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Saso\Domain\StorageLocation\LocationCode;
use Saso\Domain\StorageLocation\StorageLocation;

final class StorageLocationTest extends TestCase
{
    public function testRootHasDepthZero(): void
    {
        $root = $this->makeNode(id: 1, parentId: null, depth: 0);

        self::assertTrue($root->isRoot());
        self::assertSame(0, $root->depth);
    }

    public function testChildHasParentAndDepth(): void
    {
        $child = $this->makeNode(id: 2, parentId: 1, depth: 1);

        self::assertFalse($child->isRoot());
        self::assertSame(1, $child->parentId);
        self::assertSame(1, $child->depth);
    }

    public function testRejectsNonPositiveId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeNode(id: 0, parentId: null, depth: 0);
    }

    public function testRejectsNonPositiveParent(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeNode(id: 2, parentId: 0, depth: 1);
    }

    public function testRejectsRootWithNonZeroDepth(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeNode(id: 1, parentId: null, depth: 1);
    }

    public function testRejectsChildWithZeroDepth(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeNode(id: 2, parentId: 1, depth: 0);
    }

    public function testRejectsEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeNode(id: 1, parentId: null, depth: 0, name: '');
    }

    public function testRejectsNegativePosition(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeNode(id: 1, parentId: null, depth: 0, position: -1);
    }

    public function testWithNameIsNonMutating(): void
    {
        $a = $this->makeNode(id: 1, parentId: null, depth: 0, name: 'A');
        $b = $a->withName('B');

        self::assertSame('A', $a->name);
        self::assertSame('B', $b->name);
        self::assertNotSame($a, $b);
    }

    public function testWithPositionIsNonMutating(): void
    {
        $a = $this->makeNode(id: 1, parentId: null, depth: 0, position: 0);
        $b = $a->withPosition(5);

        self::assertSame(0, $a->position);
        self::assertSame(5, $b->position);
    }

    private function makeNode(
        int $id,
        ?int $parentId,
        int $depth,
        string $name = 'Bin',
        int $position = 0,
    ): StorageLocation {
        $now = new DateTimeImmutable('2026-04-27 10:00:00');

        return new StorageLocation(
            id: $id,
            parentId: $parentId,
            code: new LocationCode('WH1'),
            name: $name,
            position: $position,
            depth: $depth,
            createdAt: $now,
            updatedAt: $now,
        );
    }
}
