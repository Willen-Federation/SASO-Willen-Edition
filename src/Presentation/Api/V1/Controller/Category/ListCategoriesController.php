<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\Category;

use Saso\Application\Mobile\JwtGuard;
use Saso\Domain\Category\Category;
use Saso\Domain\Category\Repository\CategoryRepository;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * GET /api/v1/categories
 *
 * Returns categories as a flat list or nested tree.
 *
 * Query parameters:
 *   format  'flat' (default) | 'tree'
 */
final class ListCategoriesController
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly JwtGuard $guard,
    ) {
    }

    public function handle(HttpRequest $request): JsonResponse
    {
        $this->guard->authenticate($request);

        $format = ($request->query['format'] ?? 'flat') === 'tree' ? 'tree' : 'flat';
        $all    = $this->categories->listAll();

        if ($format === 'tree') {
            $data = $this->buildTree($all);
        } else {
            $data = array_map(self::serializeFlat(...), $all);
        }

        return new JsonResponse(
            status: 200,
            body: [
                'data'  => $data,
                'total' => count($all),
            ],
        );
    }

    /**
     * @param list<Category> $all
     * @return list<array<string, mixed>>
     */
    private function buildTree(array $all): array
    {
        $roots = array_values(array_filter($all, static fn (Category $c): bool => $c->isRoot()));
        usort($roots, static fn (Category $a, Category $b): int =>
            $a->sortOrder !== $b->sortOrder ? $a->sortOrder - $b->sortOrder : $a->id - $b->id
        );

        return array_map(fn (Category $cat): array => $this->buildNode($cat, $all), $roots);
    }

    /**
     * @param list<Category> $all
     * @return array<string, mixed>
     */
    private function buildNode(Category $cat, array $all): array
    {
        $children = array_values(array_filter(
            $all,
            static fn (Category $c): bool => $c->parentId === $cat->id,
        ));
        usort($children, static fn (Category $a, Category $b): int =>
            $a->sortOrder !== $b->sortOrder ? $a->sortOrder - $b->sortOrder : $a->id - $b->id
        );

        return array_merge(
            self::serializeFlat($cat),
            ['children' => array_map(fn (Category $c): array => $this->buildNode($c, $all), $children)],
        );
    }

    /** @return array<string, mixed> */
    private static function serializeFlat(Category $cat): array
    {
        $name = $cat->nameJa !== '' ? $cat->nameJa : $cat->nameEn;

        return [
            'id'        => (string) $cat->id,
            'name'      => $name,
            'nameEn'    => $cat->nameEn,
            'nameJa'    => $cat->nameJa,
            'parentId'  => $cat->parentId !== null ? (string) $cat->parentId : null,
            'depth'     => $cat->depth,
            'sortOrder' => $cat->sortOrder,
            'code'      => $cat->code->toString(),
            'children'  => [],
        ];
    }
}
