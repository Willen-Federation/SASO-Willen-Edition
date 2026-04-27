<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use InvalidArgumentException;
use PDO;
use Saso\Domain\Item\Attribute\AttributeValueType;
use Saso\Domain\Mcp\McpTool;

/**
 * MCP tool: `get_item_attributes`
 *
 * Returns all attribute values stored for a given item, joined with
 * the attribute definition to include labels, type, and unit.
 * No scope required — read-only.
 */
final class GetItemAttributesTool implements McpTool
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function name(): string
    {
        return 'get_item_attributes';
    }

    public function description(): string
    {
        return 'Get all custom attribute values for an inventory item, including labels and types.';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['itemId'],
            'properties' => [
                'itemId' => [
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'Item ID.',
                ],
            ],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $itemId = (int) ($input['itemId'] ?? 0);
        if ($itemId < 1) {
            throw new InvalidArgumentException('"itemId" must be a positive integer.');
        }

        $stmt = $this->pdo->prepare(
            'SELECT v.attribute_code, d.label_en, d.label_ja, d.value_type, d.unit, '.
            'v.value_string, v.value_int, v.value_float, v.value_bool '.
            'FROM item_attribute_value v '.
            'JOIN item_attribute_definition d ON v.attribute_code = d.code '.
            'WHERE v.item_id = :item '.
            'ORDER BY d.sort_order ASC, d.code ASC',
        );
        $stmt->execute(['item' => $itemId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $attributes = array_map(function (array $row): array {
            $type  = AttributeValueType::from((string) $row['value_type']);
            $value = $this->resolveValue($type, $row);

            return [
                'code'      => (string) $row['attribute_code'],
                'labelEn'   => (string) $row['label_en'],
                'labelJa'   => (string) $row['label_ja'],
                'valueType' => $type->value,
                'unit'      => isset($row['unit']) && $row['unit'] !== null ? (string) $row['unit'] : null,
                'value'     => $value,
            ];
        }, $rows);

        return [
            'itemId'     => $itemId,
            'attributes' => $attributes,
            'total'      => count($attributes),
        ];
    }

    public function requiredScope(): ?string
    {
        return null;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function resolveValue(AttributeValueType $type, array $row): mixed
    {
        if ($type === AttributeValueType::String
            || $type === AttributeValueType::Barcode
            || $type === AttributeValueType::Enum
        ) {
            return isset($row['value_string']) ? (string) $row['value_string'] : null;
        }

        if ($type === AttributeValueType::Int) {
            return $row['value_int'] !== null ? (int) $row['value_int'] : null;
        }

        if ($type === AttributeValueType::Float) {
            return $row['value_float'] !== null ? (float) $row['value_float'] : null;
        }

        return $row['value_bool'] !== null ? (int) $row['value_bool'] === 1 : null;
    }
}
