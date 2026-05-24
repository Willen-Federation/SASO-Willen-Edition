<?php

declare(strict_types=1);

namespace Saso\Application\Enrichment\Step;

final class JanLookupStep implements JanLookupStepInterface
{
    private const MAX_STRING_LENGTH = 1000;

    /**
     * @return array<string, mixed>
     */
    public function run(?string $barcodeHint): array
    {
        if (!$this->isJanBarcode($barcodeHint)) {
            return [];
        }

        $barcode = $barcodeHint;
        $url     = 'https://world.openfoodfacts.org/api/v2/product/'.urlencode($barcode).'.json';

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

        if (!is_array($data) || ($data['status'] ?? 0) !== 1) {
            return [];
        }

        $product = $data['product'] ?? [];
        if (!is_array($product)) {
            return [];
        }

        $result = ['jan_code' => $barcode];

        $name = $this->sanitiseExternalString($product['product_name'] ?? null);
        if ($name !== null) {
            $result['item_name'] = $name;
        }

        $brands = $this->sanitiseExternalString($product['brands'] ?? null);
        if ($brands !== null) {
            $result['manufacturer'] = $brands;
        }

        $ingredients = $this->sanitiseExternalString($product['ingredients_text'] ?? null);
        if ($ingredients !== null) {
            $result['description'] = $ingredients;
        }

        return $result;
    }

    private function isJanBarcode(?string $code): bool
    {
        if ($code === null) {
            return false;
        }

        if (!ctype_digit($code)) {
            return false;
        }

        $len = strlen($code);

        if ($len !== 8 && $len !== 13) {
            return false;
        }

        if ($len === 13 && (str_starts_with($code, '978') || str_starts_with($code, '979'))) {
            return false;
        }

        return true;
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
