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
 * MCP tool: `define_attribute`
 *
 * Creates a new item attribute definition, or updates an existing one
 * identified by `code`. This is the "column addition" operation — it
 * extends the item schema with a named, typed, optionally-required field.
 *
 * Supported value types: string, int, float, bool, enum, barcode.
 * Scope: `items:write`.
 */
final class DefineAttributeTool implements McpTool
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function name(): string
    {
        return 'define_attribute';
    }

    public function description(): string
    {
        return 'Create or update a custom item attribute definition (adds or modifies a typed field in the item schema).';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['code', 'labelEn', 'labelJa', 'valueType'],
            'properties' => [
                'code'            => [
                    'type'        => 'string',
                    'minLength'   => 1,
                    'maxLength'   => 120,
                    'pattern'     => '^[a-z0-9_.]+$',
                    'description' => 'Canonical key — lowercase alphanumeric + _ + . (e.g. weight, size.unit).',
                ],
                'labelEn'         => [
                    'type'        => 'string',
                    'minLength'   => 1,
                    'maxLength'   => 200,
                    'description' => 'English display label.',
                ],
                'labelJa'         => [
                    'type'        => 'string',
                    'minLength'   => 1,
                    'maxLength'   => 200,
                    'description' => 'Japanese display label.',
                ],
                'valueType'       => [
                    'type'        => 'string',
                    'enum'        => ['string', 'int', 'float', 'bool', 'enum', 'barcode'],
                    'description' => 'Data type of the attribute value.',
                ],
                'unit'            => [
                    'type'        => ['string', 'null'],
                    'maxLength'   => 40,
                    'description' => 'Display unit (e.g. kg, cm, mL). Recommended for numeric types.',
                ],
                'required'        => [
                    'type'        => 'boolean',
                    'default'     => false,
                    'description' => 'Whether all items must supply this attribute.',
                ],
                'enumValues'      => [
                    'type'        => ['array', 'null'],
                    'items'       => ['type' => 'string'],
                    'description' => 'Allowed values list. Required when valueType = enum.',
                ],
                'validationRegex' => [
                    'type'        => ['string', 'null'],
                    'description' => 'Optional PCRE regex validator (without delimiters).',
                ],
                'sortOrder'       => [
                    'type'        => 'integer',
                    'minimum'     => 0,
                    'default'     => 0,
                    'description' => 'Display sort order for UI forms.',
                ],
            ],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $code      = trim((string) ($input['code'] ?? ''));
        $labelEn   = trim((string) ($input['labelEn'] ?? ''));
        $labelJa   = trim((string) ($input['labelJa'] ?? ''));
        $valueType = (string) ($input['valueType'] ?? '');

        if ($code === '' || preg_match('/^[a-z0-9_.]+$/', $code) !== 1) {
            throw new InvalidArgumentException('"code" must be lowercase alphanumeric + _ + . only.');
        }

        if ($labelEn === '') {
            throw new InvalidArgumentException('"labelEn" must not be empty.');
        }

        if ($labelJa === '') {
            throw new InvalidArgumentException('"labelJa" must not be empty.');
        }

        try {
            $type = AttributeValueType::from($valueType);
        } catch (\ValueError) {
            throw new InvalidArgumentException(
                sprintf(
                    '"valueType" must be one of: %s.',
                    implode(', ', array_column(AttributeValueType::cases(), 'value')),
                ),
            );
        }

        $enumValues = null;
        if (isset($input['enumValues']) && is_array($input['enumValues'])) {
            $enumValues = array_values(array_map('strval', $input['enumValues']));
        }

        if ($type->requiresEnumValues()) {
            if ($enumValues === null || $enumValues === []) {
                throw new InvalidArgumentException(
                    '"enumValues" must be a non-empty list when valueType = enum.',
                );
            }
        } elseif ($enumValues !== null) {
            throw new InvalidArgumentException(
                '"enumValues" must be omitted (or null) when valueType is not enum.',
            );
        }

        $regex = (isset($input['validationRegex']) && $input['validationRegex'] !== null)
            ? trim((string) $input['validationRegex'])
            : null;

        if ($regex !== null && $regex !== '' && @preg_match('#'.$regex.'#u', '') === false) {
            throw new InvalidArgumentException('"validationRegex" is not a valid PCRE pattern.');
        }

        $regex     = ($regex === '') ? null : $regex;
        $unit      = (isset($input['unit']) && $input['unit'] !== null) ? trim((string) $input['unit']) : null;
        $required  = (bool) ($input['required'] ?? false);
        $sortOrder = max(0, (int) ($input['sortOrder'] ?? 0));
        $now       = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $enumJson  = $enumValues !== null
            ? (string) json_encode($enumValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : null;

        $existsStmt = $this->pdo->prepare(
            'SELECT id FROM item_attribute_definition WHERE code = :code LIMIT 1',
        );
        $existsStmt->execute(['code' => $code]);
        $existing = $existsStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing !== false) {
            $stmt = $this->pdo->prepare(
                'UPDATE item_attribute_definition '.
                'SET label_en = :le, label_ja = :lj, value_type = :vt, unit = :unit, '.
                'required = :req, enum_values = :ev, validation_regex = :regex, '.
                'sort_order = :sort, updated_at = :ua WHERE code = :code',
            );
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO item_attribute_definition '.
                '(code, label_en, label_ja, value_type, unit, required, enum_values, '.
                'validation_regex, sort_order, created_at, updated_at) '.
                'VALUES (:code, :le, :lj, :vt, :unit, :req, :ev, :regex, :sort, :ca, :ua)',
            );
            $stmt->bindValue('ca', $now);
        }

        $stmt->bindValue('code', $code);
        $stmt->bindValue('le', $labelEn);
        $stmt->bindValue('lj', $labelJa);
        $stmt->bindValue('vt', $type->value);
        $stmt->bindValue('unit', $unit);
        $stmt->bindValue('req', $required ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue('ev', $enumJson);
        $stmt->bindValue('regex', $regex);
        $stmt->bindValue('sort', $sortOrder, PDO::PARAM_INT);
        $stmt->bindValue('ua', $now);
        $stmt->execute();

        $rowStmt = $this->pdo->prepare(
            'SELECT * FROM item_attribute_definition WHERE code = :code LIMIT 1',
        );
        $rowStmt->execute(['code' => $code]);
        $row = $rowStmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            throw new \RuntimeException('Failed to read attribute definition after write.');
        }

        return $this->serialize($row);
    }

    public function requiredScope(): ?string
    {
        return 'items:write';
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function serialize(array $row): array
    {
        $enumValues = null;
        if (isset($row['enum_values']) && is_string($row['enum_values']) && $row['enum_values'] !== '') {
            $decoded = json_decode($row['enum_values'], associative: true);
            if (is_array($decoded)) {
                $enumValues = array_values(array_map('strval', $decoded));
            }
        }

        return [
            'id'              => (int) $row['id'],
            'code'            => (string) $row['code'],
            'labelEn'         => (string) $row['label_en'],
            'labelJa'         => (string) $row['label_ja'],
            'valueType'       => (string) $row['value_type'],
            'unit'            => isset($row['unit']) ? (string) $row['unit'] : null,
            'required'        => (int) ($row['required'] ?? 0) === 1,
            'enumValues'      => $enumValues,
            'validationRegex' => isset($row['validation_regex']) ? (string) $row['validation_regex'] : null,
            'sortOrder'       => (int) $row['sort_order'],
            'createdAt'       => (string) $row['created_at'],
            'updatedAt'       => (string) $row['updated_at'],
        ];
    }
}
