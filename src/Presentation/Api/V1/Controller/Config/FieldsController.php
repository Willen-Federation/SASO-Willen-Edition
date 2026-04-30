<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller\Config;

use PDO;
use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\HttpResponse;
use Saso\Presentation\Api\V1\Response\JsonResponse;

/**
 * GET /api/v1/config/fields
 *
 * Returns the list of `item_attribute_definition` rows that are
 * marked as visible on the mobile app (`show_on_mobile = 1`),
 * ordered by `sort_order ASC`.
 *
 * The Flutter app fetches this on startup to dynamically render
 * the item-capture form without a hard-coded field list.
 *
 * Response 200 — JSON array:
 * [
 *   {
 *     "code":         "weight",
 *     "labelEn":      "Weight",
 *     "labelJa":      "重さ",
 *     "valueType":    "float",
 *     "required":     false,
 *     "enumValues":   null,
 *     "showOnMobile": true,
 *     "sortOrder":    10
 *   },
 *   ...
 * ]
 */
final class FieldsController
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function handle(HttpRequest $request): HttpResponse
    {
        $stmt = $this->pdo->query(
            'SELECT code, label_en, label_ja, value_type, required, enum_values, show_on_mobile, sort_order '.
            'FROM item_attribute_definition '.
            'WHERE show_on_mobile = 1 '.
            'ORDER BY sort_order ASC, code ASC',
        );

        if ($stmt === false) {
            return new JsonResponse(status: 200, body: []);
        }

        $rows   = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $fields = [];

        foreach ($rows as $row) {
            $enumValues = null;
            if (isset($row['enum_values']) && is_string($row['enum_values']) && $row['enum_values'] !== '') {
                $decoded = json_decode($row['enum_values'], associative: true);
                if (is_array($decoded)) {
                    $enumValues = array_values(array_map('strval', $decoded));
                }
            }

            $fields[] = [
                'code'         => (string) $row['code'],
                'labelEn'      => (string) $row['label_en'],
                'labelJa'      => (string) $row['label_ja'],
                'valueType'    => (string) $row['value_type'],
                'required'     => (int) $row['required'] === 1,
                'enumValues'   => $enumValues,
                'showOnMobile' => (int) $row['show_on_mobile'] === 1,
                'sortOrder'    => (int) $row['sort_order'],
            ];
        }

        return new JsonResponse(status: 200, body: $fields);
    }
}
