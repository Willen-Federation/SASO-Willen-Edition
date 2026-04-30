<?php

declare(strict_types=1);

namespace Saso\Application\Enrichment\Step;

final class MergeStep
{
    /**
     * @param array<string, mixed> $base
     * @param array<string, mixed> $overlay
     * @param list<string> $userProtected
     *
     * @return array<string, mixed>
     */
    public function merge(array $base, array $overlay, array $userProtected): array
    {
        foreach ($overlay as $key => $value) {
            if (in_array($key, $userProtected, true)) {
                continue;
            }

            $existing = $base[$key] ?? null;

            if ($existing === null || $existing === '' || $existing === []) {
                $base[$key] = $value;
            }
        }

        return $base;
    }
}
