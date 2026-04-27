<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use Saso\Domain\Category\Category;
use Saso\Domain\Category\Repository\CategoryRepository;
use Saso\Domain\Mcp\McpTool;

/**
 * MCP tool: `list_categories`
 *
 * Returns classification categories either as a flat list or as a
 * nested tree depending on the `format` parameter.
 *
 * flat (default) — all categories sorted by depth then sort_order.
 * tree           — nested JSON with `children` arrays.
 *
 * Pass `parentId` with `format=flat` to list direct children only.
 *
 * Scope: none — any authenticated device can read.
 */
final class ListCategoriesTool implements McpTool
{
    public function __construct(
        private readonly CategoryRepository $categories,
    ) {
    }

    public function name(): string
    {
        return 'list_categories';
    }

    public function description(): string
    {
        return 'List classification categories. Use format=flat (default) for a simple list or format=tree for a nested children hierarchy. Pass parentId to filter direct children.';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'format'   => [
                    'type'        => 'string',
                    'enum'        => ['flat', 'tree'],
                    'description' => 'flat (default) — sorted list of all categories; tree — nested children arrays.',
                ],
                'parentId' => [
                    'type'        => ['integer', 'null'],
                    'minimum'     => 1,
                    'description' => 'Filter to direct children of this category ID (flat format only). Omit for all.',
                ],
            ],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $format = isset($input['format']) && $input['format'] === 'tree' ? 'tree' : 'flat';

        if ($format === 'tree') {
            return $this->invokeTree();
        }

        return $this->invokeFlat($input);
    }

    public function requiredScope(): ?string
    {
        return null;
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function invokeFlat(array $input): array
    {
        $parentId = isset($input['parentId']) && $input['parentId'] !== null
            ? (int) $input['parentId']
            : null;

        $list = $parentId !== null
            ? $this->categories->listChildrenOf($parentId)
            : $this->categories->listAll();

        return [
            'categories' => array_map(self::serializeFlat(...), $list),
            'total'      => count($list),
        ];
    }

    /** @return array<string, mixed> */
    private function invokeTree(): array
    {
        $all = $this->categories->listAll();

        /** @var array<int, Category> $byId */
        $byId = [];
        foreach ($all as $cat) {
            $byId[$cat->id] = $cat;
        }

        $roots = array_values(array_filter($all, static fn (Category $c): bool => $c->isRoot()));

        $trees = array_map(
            fn (Category $cat): array => $this->buildNode($cat, $byId, $all),
            $roots,
        );

        $total = array_sum(array_map($this->countNodes(...), $trees));

        return ['categories' => $trees, 'total' => $total];
    }

    /**
     * @param array<int, Category> $byId
     * @param list<Category> $all
     *
     * @return array<string, mixed>
     */
    private function buildNode(Category $cat, array $byId, array $all): array
    {
        $children = array_values(array_filter(
            $all,
            static fn (Category $c): bool => $c->parentId === $cat->id,
        ));

        usort($children, static function (Category $a, Category $b): int {
            $diff = $a->sortOrder - $b->sortOrder;

            return $diff !== 0 ? $diff : $a->id - $b->id;
        });

        $childNodes = array_map(
            fn (Category $c): array => $this->buildNode($c, $byId, $all),
            $children,
        );

        return array_merge(self::serializeFlat($cat), ['children' => $childNodes]);
    }

    /** @return array<string, mixed> */
    private static function serializeFlat(Category $cat): array
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

    /** @param array<string, mixed> $node */
    private function countNodes(array $node): int
    {
        $count = 1;
        foreach ($node['children'] as $child) {
            $count += $this->countNodes($child);
        }

        return $count;
    }
}
