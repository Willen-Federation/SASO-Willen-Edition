<?php

declare(strict_types=1);

namespace Saso\Application\Category;

use Saso\Domain\Category\Category;
use Saso\Domain\Category\Repository\CategoryRepository;

/**
 * Resolves a free-text category hint (returned by the AI vision step or by
 * users) into a concrete `category.id`. Tried in priority order:
 *
 *   1. Case-insensitive exact match against name_ja / name_en.
 *   2. Substring match ("hint" appears inside a category name or vice versa).
 *   3. Levenshtein distance ≤ LEVENSHTEIN_THRESHOLD against any name.
 *   4. Fallback to the first root category alphabetically.
 *
 * Returns null only when the category table is entirely empty — in that
 * case the caller (auto-register promotion) must surface a Failed status
 * so admins know to seed at least one category before retrying.
 */
final class CategoryHintResolver
{
    private const LEVENSHTEIN_THRESHOLD = 3;

    public function __construct(
        private readonly CategoryRepository $categories,
    ) {
    }

    public function resolve(?string $hint): ?int
    {
        $all = $this->categories->listAll();
        if ($all === []) {
            return null;
        }

        $hint = $hint !== null ? trim($hint) : '';
        if ($hint === '') {
            return $this->fallback($all);
        }

        $needle = $this->normalize($hint);

        $exact = $this->findExact($all, $needle);
        if ($exact !== null) {
            return $exact->id;
        }

        $substring = $this->findSubstring($all, $needle);
        if ($substring !== null) {
            return $substring->id;
        }

        $fuzzy = $this->findFuzzy($all, $needle);
        if ($fuzzy !== null) {
            return $fuzzy->id;
        }

        return $this->fallback($all);
    }

    /**
     * @param list<Category> $all
     */
    private function findExact(array $all, string $needle): ?Category
    {
        foreach ($all as $cat) {
            if ($this->normalize($cat->nameJa) === $needle || $this->normalize($cat->nameEn) === $needle) {
                return $cat;
            }
        }

        return null;
    }

    /**
     * @param list<Category> $all
     */
    private function findSubstring(array $all, string $needle): ?Category
    {
        foreach ($all as $cat) {
            $ja = $this->normalize($cat->nameJa);
            $en = $this->normalize($cat->nameEn);
            if (
                str_contains($ja, $needle) || str_contains($needle, $ja)
                || str_contains($en, $needle) || str_contains($needle, $en)
            ) {
                return $cat;
            }
        }

        return null;
    }

    /**
     * @param list<Category> $all
     */
    private function findFuzzy(array $all, string $needle): ?Category
    {
        // Levenshtein operates on byte strings; for multibyte (Japanese) names
        // we compare a normalised lowercase form. This is a best-effort signal
        // — we only use it for near-misses on ASCII / romaji category names.
        $best         = null;
        $bestDistance = self::LEVENSHTEIN_THRESHOLD + 1;

        foreach ($all as $cat) {
            foreach ([$cat->nameJa, $cat->nameEn] as $name) {
                $candidate = $this->normalize($name);
                if ($candidate === '') {
                    continue;
                }
                if (strlen($candidate) > 255 || strlen($needle) > 255) {
                    continue;
                }
                $distance = levenshtein($needle, $candidate);
                if ($distance < $bestDistance) {
                    $bestDistance = $distance;
                    $best         = $cat;
                }
            }
        }

        return $bestDistance <= self::LEVENSHTEIN_THRESHOLD ? $best : null;
    }

    /**
     * @param list<Category> $all
     */
    private function fallback(array $all): int
    {
        $roots = array_values(array_filter($all, static fn (Category $c): bool => $c->isRoot()));
        $pool  = $roots !== [] ? $roots : $all;

        usort(
            $pool,
            static fn (Category $a, Category $b): int => $a->sortOrder === $b->sortOrder
                ? $a->id <=> $b->id
                : $a->sortOrder <=> $b->sortOrder,
        );

        return $pool[0]->id;
    }

    private function normalize(string $value): string
    {
        $value = trim($value);
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value, 'UTF-8');
        }

        return strtolower($value);
    }
}
