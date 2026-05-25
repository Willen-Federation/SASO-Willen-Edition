<?php
namespace saso\item;

use saso\framework\Setter;
use saso\framework\View;

/**
 * GET /item/lookupCode.json?code=<JAN|EAN|ISBN>
 *
 * Returns JSON product info fetched from free public APIs:
 *   - ISBN (978/979 prefix, 13 digits) → Open Library
 *   - JAN/EAN                          → Open Food Facts (Japanese product names preferred)
 *
 * Response shape:
 *   { error: null|string, data: null|{ name, ... } }
 */
final class CodeLookupView implements View
{
    use Setter;
    private \Closure $content;

    public function __construct(private array $query)
    {
    }

    public function display(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        $code = preg_replace('/[^0-9X]/', '', strtoupper((string)($this->query['code'] ?? '')));

        if ($code === '' || (strlen($code) < 8 || strlen($code) > 14)) {
            echo json_encode(['error' => 'invalid_code', 'data' => null]);
            return;
        }

        $data = $this->isIsbn($code)
            ? $this->lookupIsbn($code)
            : $this->lookupJan($code);

        echo json_encode(['error' => null, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function isIsbn(string $code): bool
    {
        return strlen($code) === 13
            && (str_starts_with($code, '978') || str_starts_with($code, '979'));
    }

    private function ctx(): mixed
    {
        return stream_context_create(['http' => [
            'timeout'        => 5,
            'ignore_errors'  => true,
            'header'         => "User-Agent: SASO-ItemLookup/1.0\r\n",
        ]]);
    }

    private function lookupIsbn(string $code): ?array
    {
        $url  = 'https://openlibrary.org/api/books?' . http_build_query([
            'bibkeys' => 'ISBN:' . $code,
            'format'  => 'json',
            'jscmd'   => 'data',
        ]);
        $json = @file_get_contents($url, false, $this->ctx());
        if ($json === false) {
            return null;
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded) || count($decoded) === 0) {
            return null;
        }
        $book = reset($decoded);
        if (!is_array($book)) {
            return null;
        }
        $authors = array_column($book['authors'] ?? [], 'name');
        return array_filter([
            'name'      => ($book['title'] ?? null) ?: null,
            'subtitle'  => ($book['subtitle'] ?? null) ?: null,
            'author'    => $authors ? implode(', ', $authors) : null,
            'publisher' => ($book['publishers'][0]['name'] ?? null) ?: null,
            'year'      => ($book['publish_date'] ?? null) ?: null,
        ]);
    }

    private function lookupJan(string $code): ?array
    {
        $url  = 'https://world.openfoodfacts.org/api/v0/product/' . rawurlencode($code) . '.json';
        $json = @file_get_contents($url, false, $this->ctx());
        if ($json === false) {
            return null;
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded) || (int)($decoded['status'] ?? 0) !== 1) {
            return null;
        }
        $p = $decoded['product'] ?? [];
        $name = ($p['product_name_ja'] ?? '')
            ?: ($p['product_name'] ?? '');
        return array_filter([
            'name'     => $name ?: null,
            'brand'    => ($p['brands'] ?? null) ?: null,
            'category' => ($p['categories_tags_ja'] ?? null)
                           ? implode(', ', (array)($p['categories_tags_ja'])) : null,
        ]);
    }

    public function onRoot(): bool
    {
        return false;
    }

    public function getTitle(): string
    {
        return '';
    }

    public function getContent(): \Closure
    {
        return $this->content;
    }
}
