<?php

declare(strict_types=1);

namespace Saso\Application\Enrichment\Step;

/**
 * Enriches draft data using AI-collected keywords and identifiers.
 *
 * After AiVisionStep extracts item_name, manufacturer, etc., this step
 * uses those keywords to query external libraries (OpenBD for books,
 * Open Library for general items) to fill gaps that the image alone
 * cannot provide.
 *
 * Gracefully returns empty array if the lookup fails or if there's
 * insufficient data to query with.
 */
final class KeywordLookupStep implements KeywordLookupStepInterface
{
    private const OPENBD_API = 'https://api.openbd.jp/v1/get';
    private const OPENLIBRARY_SEARCH_API = 'https://openlibrary.org/search.json';

    /**
     * @param array<string, mixed> $existing Already-enriched data from prior steps
     *
     * @return array<string, mixed>
     */
    public function run(array $existing): array
    {
        $itemName = $existing['item_name'] ?? null;
        $isbn = $existing['isbn'] ?? null;

        if (empty($itemName)) {
            return [];
        }

        if (!empty($isbn) && is_string($isbn)) {
            return $this->lookupByIsbn($isbn);
        }

        if (is_string($itemName)) {
            return $this->lookupByKeyword($itemName);
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function lookupByIsbn(string $isbn): array
    {
        try {
            $url = self::OPENBD_API.'?isbn='.urlencode($isbn);
            $json = $this->fetchUrl($url);

            if ($json === null) {
                return [];
            }

            $data = json_decode($json, true);
            if (!is_array($data) || count($data) === 0) {
                return [];
            }

            $book = $data[0];
            if (!is_array($book)) {
                return [];
            }

            $summary = $book['summary'] ?? null;
            $publisher = $book['publisher'] ?? null;

            $result = [];
            if (!empty($summary) && is_string($summary)) {
                $result['description'] = $summary;
            }
            if (!empty($publisher) && is_string($publisher)) {
                $result['manufacturer'] = $publisher;
            }

            return $result;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function lookupByKeyword(string $keyword): array
    {
        try {
            $url = self::OPENLIBRARY_SEARCH_API.'?q='.urlencode($keyword).'&limit=1';
            $json = $this->fetchUrl($url);

            if ($json === null) {
                return [];
            }

            $data = json_decode($json, true);
            if (!is_array($data) || !isset($data['docs']) || !is_array($data['docs'])) {
                return [];
            }

            $docs = $data['docs'];
            if (count($docs) === 0) {
                return [];
            }

            $doc = $docs[0];
            if (!is_array($doc)) {
                return [];
            }

            $result = [
                'publication_year' => null,
                'manufacturer' => null,
                'item_name' => null,
            ];

            if (isset($doc['first_publish_year']) && is_numeric($doc['first_publish_year'])) {
                $result['publication_year'] = (int) $doc['first_publish_year'];
            }

            if (isset($doc['author_name']) && is_array($doc['author_name']) && count($doc['author_name']) > 0) {
                $author = $doc['author_name'][0];
                if (is_string($author) && $result['manufacturer'] === null) {
                    $result['manufacturer'] = $author;
                }
            }

            if (isset($doc['title']) && is_string($doc['title']) && $result['item_name'] === null) {
                $result['item_name'] = $doc['title'];
            }

            $result = array_filter($result, static fn ($v) => $v !== null);

            return $result;
        } catch (\Throwable) {
            return [];
        }
    }

    private function fetchUrl(string $url): ?string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error !== '' || $response === false) {
            return null;
        }

        return is_string($response) ? $response : null;
    }
}
