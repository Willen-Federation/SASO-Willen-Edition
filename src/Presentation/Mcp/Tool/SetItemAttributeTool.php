<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PDO;
use Saso\Domain\Item\Attribute\AttributeValueType;
use Saso\Domain\Mcp\McpTool;

/**
 * MCP tool: `set_item_attribute`
 *
 * Sets or updates a single typed attribute value on an item. The value
 * is coerced to the type declared in `item_attribute_definition`. For
 * enum types, the value is validated against the allowed list.
 *
 * Scope: `items:write`.
 */
final class SetItemAttributeTool implements McpTool
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function name(): string
    {
        return 'set_item_attribute';
    }

    public function description(): string
    {
        return 'Set or update a custom attribute value for an inventory item.';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['itemId', 'code', 'value'],
            'properties' => [
                'itemId' => [
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'Item ID.',
                ],
                'code'   => [
                    'type'        => 'string',
                    'minLength'   => 1,
                    'maxLength'   => 120,
                    'description' => 'Attribute code (e.g. weight, color.primary). Must exist in define_attribute.',
                ],
                'value'  => [
                    'description' => 'The attribute value. Type must match the attribute definition (string, int, float, bool, or enum value).',
                ],
            ],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $itemId = (int) ($input['itemId'] ?? 0);
        $code   = trim((string) ($input['code'] ?? ''));

        if ($itemId < 1) {
            throw new InvalidArgumentException('"itemId" must be a positive integer.');
        }

        if ($code === '') {
            throw new InvalidArgumentException('"code" must not be empty.');
        }

        if (!array_key_exists('value', $input)) {
            throw new InvalidArgumentException('"value" is required.');
        }

        $defStmt = $this->pdo->prepare(
            'SELECT value_type, enum_values FROM item_attribute_definition WHERE code = :code LIMIT 1',
        );
        $defStmt->execute(['code' => $code]);
        $def = $defStmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($def)) {
            throw new InvalidArgumentException(
                sprintf('Attribute "%s" is not defined. Use define_attribute to create it first.', $code),
            );
        }

        $type     = AttributeValueType::from((string) $def['value_type']);
        $rawValue = $input['value'];

        [$vStr, $vInt, $vFlt, $vBool] = $this->coerce($type, $rawValue, $def);

        $now = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');

        $update = $this->pdo->prepare(
            'UPDATE item_attribute_value '.
            'SET value_string = :vs, value_int = :vi, value_float = :vf, value_bool = :vb, updated_at = :ua '.
            'WHERE item_id = :item AND attribute_code = :code',
        );
        $update->bindValue('vs', $vStr);
        $update->bindValue('vi', $vInt, $vInt === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $update->bindValue('vf', $vFlt);
        $update->bindValue('vb', $vBool !== null ? ($vBool ? 1 : 0) : null, $vBool === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $update->bindValue('ua', $now);
        $update->bindValue('item', $itemId, PDO::PARAM_INT);
        $update->bindValue('code', $code);
        $update->execute();

        if ($update->rowCount() === 0) {
            $insert = $this->pdo->prepare(
                'INSERT INTO item_attribute_value '.
                '(item_id, attribute_code, value_string, value_int, value_float, value_bool, created_at, updated_at) '.
                'VALUES (:item, :code, :vs, :vi, :vf, :vb, :ca, :ua)',
            );
            $insert->bindValue('item', $itemId, PDO::PARAM_INT);
            $insert->bindValue('code', $code);
            $insert->bindValue('vs', $vStr);
            $insert->bindValue('vi', $vInt, $vInt === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $insert->bindValue('vf', $vFlt);
            $insert->bindValue('vb', $vBool !== null ? ($vBool ? 1 : 0) : null, $vBool === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $insert->bindValue('ca', $now);
            $insert->bindValue('ua', $now);
            $insert->execute();
        }

        return [
            'itemId'    => $itemId,
            'code'      => $code,
            'valueType' => $type->value,
            'value'     => $this->typedValue($type, $vStr, $vInt, $vFlt, $vBool),
        ];
    }

    public function requiredScope(): ?string
    {
        return 'items:write';
    }

    /**
     * Coerce raw input to the typed storage columns.
     *
     * @param array<string, mixed> $def
     *
     * @return array{?string, ?int, ?float, ?bool}
     */
    private function coerce(AttributeValueType $type, mixed $rawValue, array $def): array
    {
        $vStr  = null;
        $vInt  = null;
        $vFlt  = null;
        $vBool = null;

        if ($type === AttributeValueType::String || $type === AttributeValueType::Barcode) {
            $vStr = (string) $rawValue;
        } elseif ($type === AttributeValueType::Int) {
            $vInt = (int) $rawValue;
        } elseif ($type === AttributeValueType::Float) {
            $vFlt = (float) $rawValue;
        } elseif ($type === AttributeValueType::Bool) {
            $coerced = filter_var($rawValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($coerced === null) {
                throw new InvalidArgumentException('"value" must be a boolean for this attribute.');
            }
            $vBool = $coerced;
        } elseif ($type === AttributeValueType::Enum) {
            $val     = (string) $rawValue;
            $allowed = [];
            if (isset($def['enum_values']) && is_string($def['enum_values']) && $def['enum_values'] !== '') {
                $decoded = json_decode($def['enum_values'], associative: true);
                if (is_array($decoded)) {
                    $allowed = array_values(array_map('strval', $decoded));
                }
            }
            if ($allowed !== [] && !in_array($val, $allowed, true)) {
                throw new InvalidArgumentException(
                    sprintf('"value" must be one of: %s.', implode(', ', $allowed)),
                );
            }
            $vStr = $val;
        }

        return [$vStr, $vInt, $vFlt, $vBool];
    }

    private function typedValue(
        AttributeValueType $type,
        ?string $vStr,
        ?int $vInt,
        ?float $vFlt,
        ?bool $vBool,
    ): mixed {
        if ($type === AttributeValueType::String
            || $type === AttributeValueType::Barcode
            || $type === AttributeValueType::Enum
        ) {
            return $vStr;
        }

        if ($type === AttributeValueType::Int) {
            return $vInt;
        }

        if ($type === AttributeValueType::Float) {
            return $vFlt;
        }

        return $vBool;
    }
}
