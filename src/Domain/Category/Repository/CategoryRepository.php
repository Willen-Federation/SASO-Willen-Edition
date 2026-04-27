<?php

declare(strict_types=1);

namespace Saso\Domain\Category\Repository;

use Saso\Domain\Category\Category;
use Saso\Domain\Category\CategoryCode;

interface CategoryRepository
{
    public function findById(int $id): ?Category;

    public function findByCode(CategoryCode $code): ?Category;

    /**
     * @return list<Category>
     */
    public function listRoots(): array;

    /**
     * @return list<Category>
     */
    public function listChildrenOf(int $parentId): array;

    /**
     * @return list<Category>
     */
    public function listAll(): array;

    public function save(Category $category): Category;

    public function delete(int $id): void;
}
