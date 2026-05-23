<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Application\Category;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Saso\Application\Category\CategoryHintResolver;
use Saso\Domain\Category\Category;
use Saso\Domain\Category\CategoryCode;
use Saso\Domain\Category\Repository\CategoryRepository;

final class CategoryHintResolverTest extends TestCase
{
    public function testReturnsNullWhenCategoriesAreEmpty(): void
    {
        $resolver = new CategoryHintResolver(new InMemoryCategoryRepo([]));
        self::assertNull($resolver->resolve('Electronics'));
    }

    public function testExactMatchAgainstJapaneseName(): void
    {
        $resolver = new CategoryHintResolver(new InMemoryCategoryRepo([
            $this->cat(1, 'ELECTRONICS', 'Electronics', '電子機器'),
            $this->cat(2, 'BOOKS', 'Books', '書籍'),
        ]));

        self::assertSame(1, $resolver->resolve('電子機器'));
        self::assertSame(2, $resolver->resolve('書籍'));
    }

    public function testExactMatchIsCaseInsensitiveOnEnglish(): void
    {
        $resolver = new CategoryHintResolver(new InMemoryCategoryRepo([
            $this->cat(1, 'ELECTRONICS', 'Electronics', '電子機器'),
        ]));

        self::assertSame(1, $resolver->resolve('ELECTRONICS'));
    }

    public function testSubstringMatchUsedWhenExactMissing(): void
    {
        $resolver = new CategoryHintResolver(new InMemoryCategoryRepo([
            $this->cat(1, 'ELECTRONICS', 'Electronics', '電子機器'),
            $this->cat(2, 'BOOKS', 'Books', '書籍'),
        ]));

        // "家電" not present, but "電子機器" contains "電子" — substring match.
        self::assertSame(1, $resolver->resolve('電子'));
    }

    public function testLevenshteinFuzzyMatchOnRomaji(): void
    {
        $resolver = new CategoryHintResolver(new InMemoryCategoryRepo([
            $this->cat(1, 'ELECTRONICS', 'Electronics', '電子機器'),
            $this->cat(2, 'BOOKS', 'Books', '書籍'),
        ]));

        // "Electronix" → distance 1 from "electronics".
        self::assertSame(1, $resolver->resolve('Electronix'));
    }

    public function testFallsBackToFirstRootWhenNothingMatches(): void
    {
        $resolver = new CategoryHintResolver(new InMemoryCategoryRepo([
            $this->cat(7, 'MISC', 'Miscellaneous', 'その他', null, 0, 5),
            $this->cat(3, 'FIRST', 'First', '最初', null, 0, 1),
            $this->cat(9, 'LEAF', 'Leaf', '葉', 3, 1, 0),
        ]));

        self::assertSame(3, $resolver->resolve('totally-unrelated-string-zzz'));
    }

    public function testFallsBackWhenHintIsNullOrEmpty(): void
    {
        $resolver = new CategoryHintResolver(new InMemoryCategoryRepo([
            $this->cat(1, 'ELECTRONICS', 'Electronics', '電子機器'),
        ]));

        self::assertSame(1, $resolver->resolve(null));
        self::assertSame(1, $resolver->resolve(''));
        self::assertSame(1, $resolver->resolve('   '));
    }

    private function cat(
        int $id,
        string $code,
        string $nameEn,
        string $nameJa,
        ?int $parentId = null,
        int $depth = 0,
        int $sortOrder = 0,
    ): Category {
        $now = new DateTimeImmutable();

        return new Category(
            id: $id,
            code: new CategoryCode($code),
            nameEn: $nameEn,
            nameJa: $nameJa,
            parentId: $parentId,
            depth: $depth,
            sortOrder: $sortOrder,
            description: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }
}

final class InMemoryCategoryRepo implements CategoryRepository
{
    /**
     * @param list<Category> $categories
     */
    public function __construct(
        private array $categories,
    ) {
    }

    public function findById(int $id): ?Category
    {
        foreach ($this->categories as $cat) {
            if ($cat->id === $id) {
                return $cat;
            }
        }

        return null;
    }

    public function findByCode(CategoryCode $code): ?Category
    {
        foreach ($this->categories as $cat) {
            if ($cat->code->toString() === $code->toString()) {
                return $cat;
            }
        }

        return null;
    }

    public function listRoots(): array
    {
        return array_values(array_filter(
            $this->categories,
            static fn (Category $c): bool => $c->isRoot(),
        ));
    }

    public function listChildrenOf(int $parentId): array
    {
        return array_values(array_filter(
            $this->categories,
            static fn (Category $c): bool => $c->parentId === $parentId,
        ));
    }

    public function listAll(): array
    {
        return $this->categories;
    }

    public function save(Category $category): Category
    {
        $this->categories[] = $category;

        return $category;
    }

    public function delete(int $id): void
    {
        $this->categories = array_values(array_filter(
            $this->categories,
            static fn (Category $c): bool => $c->id !== $id,
        ));
    }
}
