<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PDO;
use Saso\Domain\Category\Category;
use Saso\Domain\Category\CategoryCode;
use Saso\Domain\Category\Repository\CategoryRepository;
use Saso\Domain\Mcp\McpTool;

/**
 * MCP tool: `manage_category`
 *
 * Unified create / update / delete for classification categories.
 *
 * Typical hierarchy: FOOD → FOOD-FRESH → FOOD-FRESH-VEG
 *
 * create — inserts a new category. `code` (uppercase + hyphens, e.g.
 *           FOOD-FRESH) and `nameEn` + `nameJa` are required.
 *           `parentId`, `description`, and `sortOrder` are optional.
 * update — updates any writable field for an existing category.
 * delete — removes the category; children's parent_id becomes null.
 *
 * Scope: `items:write`.
 */
final class ManageCategoryTool implements McpTool
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly CategoryRepository $categories,
    ) {
    }

    public function name(): string
    {
        return 'manage_category';
    }

    public function description(): string
    {
        return 'Create, update, or delete a classification category. Hierarchical codes like FOOD-FRESH-VEG allow precise item classification.';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['action'],
            'properties' => [
                'action'      => [
                    'type'        => 'string',
                    'enum'        => ['create', 'update', 'delete'],
                    'description' => 'Operation to perform.',
                ],
                'id'          => [
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'Category ID. Required for update and delete.',
                ],
                'code'        => [
                    'type'        => 'string',
                    'minLength'   => 1,
                    'maxLength'   => 64,
                    'description' => 'Unique classification code — uppercase alphanumeric + hyphens (e.g. FOOD-FRESH). Required for create.',
                ],
                'nameEn'      => [
                    'type'        => 'string',
                    'minLength'   => 1,
                    'maxLength'   => 200,
                    'description' => 'English category name. Required for create.',
                ],
                'nameJa'      => [
                    'type'        => 'string',
                    'minLength'   => 1,
                    'maxLength'   => 200,
                    'description' => 'Japanese category name (e.g. 食品・生鮮・野菜). Required for create.',
                ],
                'parentId'    => [
                    'type'        => ['integer', 'null'],
                    'minimum'     => 1,
                    'description' => 'Parent category ID for hierarchical placement. null = root.',
                ],
                'sortOrder'   => [
                    'type'        => 'integer',
                    'minimum'     => 0,
                    'description' => 'Sibling display order (default 0).',
                ],
                'description' => [
                    'type'        => ['string', 'null'],
                    'description' => 'Optional free-text description.',
                ],
            ],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $action = (string) ($input['action'] ?? '');

        return match ($action) {
            'create' => $this->create($input),
            'update' => $this->update($input),
            'delete' => $this->delete($input),
            default  => throw new InvalidArgumentException(
                '"action" must be one of: create, update, delete.',
            ),
        };
    }

    public function requiredScope(): ?string
    {
        return 'items:write';
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function create(array $input): array
    {
        $codeStr  = trim((string) ($input['code'] ?? ''));
        $nameEn   = trim((string) ($input['nameEn'] ?? ''));
        $nameJa   = trim((string) ($input['nameJa'] ?? ''));
        $parentId = isset($input['parentId']) && $input['parentId'] !== null
            ? (int) $input['parentId']
            : null;
        $sortOrder   = max(0, (int) ($input['sortOrder'] ?? 0));
        $description = $this->parseNullableString($input, 'description');

        if ($codeStr === '') {
            throw new InvalidArgumentException('"code" is required for create.');
        }
        if ($nameEn === '') {
            throw new InvalidArgumentException('"nameEn" is required for create.');
        }
        if ($nameJa === '') {
            throw new InvalidArgumentException('"nameJa" is required for create.');
        }

        try {
            $code = new CategoryCode($codeStr);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                '"code" must be uppercase alphanumeric segments joined by hyphens (e.g. FOOD-FRESH): '.$e->getMessage(),
            );
        }

        $depth = 0;
        if ($parentId !== null) {
            $parent = $this->categories->findById($parentId);
            if ($parent === null) {
                throw new InvalidArgumentException(sprintf('Parent category %d does not exist.', $parentId));
            }
            $depth = $parent->depth + 1;
        }

        $now  = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO category (parent_id, code, name_en, name_ja, depth, sort_order, description, created_at, updated_at) '.
            'VALUES (:parent, :code, :name_en, :name_ja, :depth, :sort, :desc, :ca, :ua)',
        );
        $stmt->bindValue('parent', $parentId, $parentId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue('code', $code->toString());
        $stmt->bindValue('name_en', $nameEn);
        $stmt->bindValue('name_ja', $nameJa);
        $stmt->bindValue('depth', $depth, PDO::PARAM_INT);
        $stmt->bindValue('sort', $sortOrder, PDO::PARAM_INT);
        $stmt->bindValue('desc', $description);
        $stmt->bindValue('ca', $now);
        $stmt->bindValue('ua', $now);
        $stmt->execute();

        $newId    = (int) $this->pdo->lastInsertId();
        $category = $this->categories->findById($newId);

        if ($category === null) {
            throw new \RuntimeException('Failed to read category after create.');
        }

        return ['action' => 'created', 'category' => $this->serialize($category)];
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function update(array $input): array
    {
        $id = (int) ($input['id'] ?? 0);
        if ($id < 1) {
            throw new InvalidArgumentException('"id" is required for update.');
        }

        $category = $this->categories->findById($id);
        if ($category === null) {
            throw new InvalidArgumentException(sprintf('Category %d does not exist.', $id));
        }

        $nameEn    = array_key_exists('nameEn', $input) ? trim((string) $input['nameEn']) : $category->nameEn;
        $nameJa    = array_key_exists('nameJa', $input) ? trim((string) $input['nameJa']) : $category->nameJa;
        $sortOrder = array_key_exists('sortOrder', $input) ? max(0, (int) $input['sortOrder']) : $category->sortOrder;

        if ($nameEn === '') {
            throw new InvalidArgumentException('"nameEn" must not be empty.');
        }
        if ($nameJa === '') {
            throw new InvalidArgumentException('"nameJa" must not be empty.');
        }

        $code = $category->code;
        if (array_key_exists('code', $input)) {
            try {
                $code = new CategoryCode(trim((string) $input['code']));
            } catch (InvalidArgumentException $e) {
                throw new InvalidArgumentException('"code" must be uppercase alphanumeric + hyphens: '.$e->getMessage());
            }
        }

        $description = array_key_exists('description', $input)
            ? $this->parseNullableString($input, 'description')
            : $category->description;

        $updated = new Category(
            id: $category->id,
            code: $code,
            nameEn: $nameEn,
            nameJa: $nameJa,
            parentId: $category->parentId,
            depth: $category->depth,
            sortOrder: $sortOrder,
            description: $description,
            createdAt: $category->createdAt,
            updatedAt: $category->updatedAt,
        );

        $saved = $this->categories->save($updated);

        return ['action' => 'updated', 'category' => $this->serialize($saved)];
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function delete(array $input): array
    {
        $id = (int) ($input['id'] ?? 0);
        if ($id < 1) {
            throw new InvalidArgumentException('"id" is required for delete.');
        }

        $exists = $this->categories->findById($id);
        if ($exists === null) {
            throw new InvalidArgumentException(sprintf('Category %d does not exist.', $id));
        }

        $this->categories->delete($id);

        return ['action' => 'deleted', 'id' => $id];
    }

    /** @return array<string, mixed> */
    private function serialize(Category $cat): array
    {
        return [
            'id'          => $cat->id,
            'code'        => $cat->code->toString(),
            'nameEn'      => $cat->nameEn,
            'nameJa'      => $cat->nameJa,
            'parentId'    => $cat->parentId,
            'depth'       => $cat->depth,
            'sortOrder'   => $cat->sortOrder,
            'description' => $cat->description,
        ];
    }

    /** @param array<string, mixed> $input */
    private function parseNullableString(array $input, string $key): ?string
    {
        if (!isset($input[$key])) {
            return null;
        }
        $val = trim((string) $input[$key]);

        return $val === '' ? null : $val;
    }
}
