<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use Saso\Domain\Mcp\McpTool;
use Saso\Domain\Search\SearchHit;
use Saso\Domain\Search\SearchIndex;
use Saso\Domain\Search\SearchQuery;

/**
 * MCP tool: `search_items`
 *
 * Performs a keyword search over the item catalogue using the configured
 * {@see SearchIndex} (NullSearchIndex returns an empty list when no vector
 * backend is wired). Scope: none — any authenticated device can read.
 */
final class SearchItemsTool implements McpTool
{
    public function __construct(
        private readonly SearchIndex $index,
    ) {
    }

    public function name(): string
    {
        return 'search_items';
    }

    public function description(): string
    {
        return 'Search the item catalogue by keyword. Returns matching items with id, name, and category.';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['query'],
            'properties' => [
                'query' => [
                    'type'        => 'string',
                    'description' => 'Keyword to search for.',
                    'minLength'   => 1,
                    'maxLength'   => 200,
                ],
                'limit' => [
                    'type'        => 'integer',
                    'description' => 'Maximum number of results (default 20, max 100).',
                    'minimum'     => 1,
                    'maximum'     => 100,
                    'default'     => 20,
                ],
            ],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $query = trim((string) ($input['query'] ?? ''));
        $limit = min(100, max(1, (int) ($input['limit'] ?? 20)));

        if ($query === '') {
            return ['items' => [], 'total' => 0];
        }

        $result = $this->index->search(new SearchQuery($query, [], $limit));

        return [
            'items' => array_map(
                static fn (SearchHit $hit): array => [
                    'id'    => $hit->id,
                    'name'  => (string) ($hit->document['name'] ?? ''),
                    'score' => $hit->score,
                ],
                $result->hits,
            ),
            'total' => $result->total,
        ];
    }

    public function requiredScope(): ?string
    {
        return null;
    }
}
