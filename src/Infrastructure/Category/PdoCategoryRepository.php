<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Category;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Saso\Domain\Category\Category;
use Saso\Domain\Category\CategoryCode;
use Saso\Domain\Category\Repository\CategoryRepository;

final class PdoCategoryRepository implements CategoryRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly DateTimeZone $timezone = new DateTimeZone('UTC'),
    ) {
    }

    public function findById(int $id): ?Category
    {
        $stmt = $this->pdo->prepare('SELECT * FROM category WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    public function findByCode(CategoryCode $code): ?Category
    {
        $stmt = $this->pdo->prepare('SELECT * FROM category WHERE code = :code');
        $stmt->execute(['code' => $code->toString()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    public function listRoots(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM category WHERE parent_id IS NULL ORDER BY sort_order ASC, id ASC',
        );
        if ($stmt === false) {
            return [];
        }

        return array_map(
            fn (array $row): Category => $this->hydrate($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    public function listChildrenOf(int $parentId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM category WHERE parent_id = :pid ORDER BY sort_order ASC, id ASC',
        );
        $stmt->execute(['pid' => $parentId]);

        return array_map(
            fn (array $row): Category => $this->hydrate($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    public function listAll(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM category ORDER BY depth ASC, sort_order ASC, id ASC',
        );
        if ($stmt === false) {
            return [];
        }

        return array_map(
            fn (array $row): Category => $this->hydrate($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    public function save(Category $category): Category
    {
        $now      = (new DateTimeImmutable('now', $this->timezone))->format('Y-m-d H:i:s');
        $existing = $this->findById($category->id);

        if ($existing === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO category (id, code, parent_id, depth, sort_order, name_en, name_ja, description, created_at, updated_at) '.
                'VALUES (:id, :code, :parent, :depth, :sort, :name_en, :name_ja, :desc, :ca, :ua)',
            );
            $stmt->bindValue('id', $category->id, PDO::PARAM_INT);
            $stmt->bindValue('code', $category->code->toString());
            $stmt->bindValue('parent', $category->parentId, $category->parentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue('depth', $category->depth, PDO::PARAM_INT);
            $stmt->bindValue('sort', $category->sortOrder, PDO::PARAM_INT);
            $stmt->bindValue('name_en', $category->nameEn);
            $stmt->bindValue('name_ja', $category->nameJa);
            $stmt->bindValue('desc', $category->description);
            $stmt->bindValue('ca', $category->createdAt->setTimezone($this->timezone)->format('Y-m-d H:i:s'));
            $stmt->bindValue('ua', $now);
            $stmt->execute();
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE category SET code = :code, parent_id = :parent, depth = :depth, sort_order = :sort, '.
                'name_en = :name_en, name_ja = :name_ja, description = :desc, updated_at = :ua WHERE id = :id',
            );
            $stmt->bindValue('id', $category->id, PDO::PARAM_INT);
            $stmt->bindValue('code', $category->code->toString());
            $stmt->bindValue('parent', $category->parentId, $category->parentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue('depth', $category->depth, PDO::PARAM_INT);
            $stmt->bindValue('sort', $category->sortOrder, PDO::PARAM_INT);
            $stmt->bindValue('name_en', $category->nameEn);
            $stmt->bindValue('name_ja', $category->nameJa);
            $stmt->bindValue('desc', $category->description);
            $stmt->bindValue('ua', $now);
            $stmt->execute();
        }

        $reread = $this->findById($category->id);
        if ($reread === null) {
            throw new \RuntimeException(sprintf(
                'PdoCategoryRepository::save lost row %d after write.',
                $category->id,
            ));
        }

        return $reread;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM category WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): Category
    {
        $parent = $row['parent_id'] ?? null;

        return new Category(
            id: (int) $row['id'],
            code: new CategoryCode((string) $row['code']),
            nameEn: (string) $row['name_en'],
            nameJa: (string) $row['name_ja'],
            parentId: $parent === null ? null : (int) $parent,
            depth: (int) $row['depth'],
            sortOrder: (int) $row['sort_order'],
            description: isset($row['description']) && is_string($row['description']) ? $row['description'] : null,
            createdAt: new DateTimeImmutable((string) $row['created_at'], $this->timezone),
            updatedAt: new DateTimeImmutable((string) $row['updated_at'], $this->timezone),
        );
    }
}
