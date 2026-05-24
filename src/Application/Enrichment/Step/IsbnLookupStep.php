<?php

declare(strict_types=1);

namespace Saso\Application\Enrichment\Step;

final class IsbnLookupStep implements IsbnLookupStepInterface
{
    private const MAX_STRING_LENGTH = 1000;

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
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);

        $raw = curl_exec($ch);
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

        $title = $this->sanitiseExternalString($book['title'] ?? null);
        if ($title !== null) {
            $result['item_name'] = $title;
        }

        $publishers = $book['publishers'] ?? [];
        if (is_array($publishers) && isset($publishers[0]['name'])) {
            $publisher = $this->sanitiseExternalString($publishers[0]['name']);
            if ($publisher !== null) {
                $result['manufacturer'] = $publisher;
            }
        }

        $description = $book['notes'] ?? ($book['subtitle'] ?? null);
        $description = $this->sanitiseExternalString($description);
        if ($description !== null) {
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

    /**
     * Trim, strip control chars, and cap length on strings returned by the
     * external API. Untrusted upstream data must not bypass the field-length
     * constraints the rest of the pipeline assumes.
     */
    private function sanitiseExternalString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        // Strip C0 control characters except for tab/newline to avoid log
        // forging or hidden payloads sneaking into prompts and item names.
        $stripped = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        if (!is_string($stripped)) {
            return null;
        }
        $trimmed = trim($stripped);
        if ($trimmed === '') {
            return null;
        }

        return mb_substr($trimmed, 0, self::MAX_STRING_LENGTH);
    }
}
