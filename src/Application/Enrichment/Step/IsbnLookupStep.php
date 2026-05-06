<?php

declare(strict_types=1);

namespace Saso\Application\Enrichment\Step;

final class IsbnLookupStep implements IsbnLookupStepInterface
{
    /**
     * @return array<string, mixed>
     */
    public function run(?string $barcodeHint): array
    {
        if (!$this->isIsbn13($barcodeHint)) {
            return [];
        }

        $isbn = $barcodeHint;
        $url  = 'https://openlibrary.org/api/books?bibkeys=ISBN:'.urlencode($isbn).'&format=json&jscmd=data';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);

        $raw     = curl_exec($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $raw === '') {
            return [];
        }

        $data = json_decode((string) $raw, associative: true);

        if (!is_array($data)) {
            return [];
        }

        $key  = 'ISBN:'.$isbn;
        $book = $data[$key] ?? null;

        if (!is_array($book)) {
            return [];
        }

        $result = ['isbn' => $isbn];

        $title = $book['title'] ?? null;
        if (is_string($title) && $title !== '') {
            $result['item_name'] = $title;
        }

        $publishers = $book['publishers'] ?? [];
        if (is_array($publishers) && isset($publishers[0]['name'])) {
            $result['manufacturer'] = $publishers[0]['name'];
        }

        $description = $book['notes'] ?? ($book['subtitle'] ?? null);
        if (is_string($description) && $description !== '') {
            $result['description'] = $description;
        }

        return $result;
    }

    private function isIsbn13(?string $code): bool
    {
        if ($code === null) {
            return false;
        }

        return strlen($code) === 13
            && ctype_digit($code)
            && (str_starts_with($code, '978') || str_starts_with($code, '979'));
    }
}
