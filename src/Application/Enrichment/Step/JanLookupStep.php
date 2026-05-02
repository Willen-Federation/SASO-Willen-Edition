<?php

declare(strict_types=1);

namespace Saso\Application\Enrichment\Step;

final class JanLookupStep implements JanLookupStepInterface
{
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

        if (!is_array($data) || ($data['status'] ?? 0) !== 1) {
            return [];
        }

        $product = $data['product'] ?? [];
        if (!is_array($product)) {
            return [];
        }

        $result = ['jan_code' => $barcode];

        $name = $product['product_name'] ?? null;
        if (is_string($name) && $name !== '') {
            $result['item_name'] = $name;
        }

        $brands = $product['brands'] ?? null;
        if (is_string($brands) && $brands !== '') {
            $result['manufacturer'] = $brands;
        }

        $ingredients = $product['ingredients_text'] ?? null;
        if (is_string($ingredients) && $ingredients !== '') {
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
}
