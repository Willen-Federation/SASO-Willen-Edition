<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Item\Attribute;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Saso\Domain\Item\Attribute\AttributeCode;
use Saso\Domain\Item\Attribute\AttributeDefinition;
use Saso\Domain\Item\Attribute\AttributeValueType;
use Saso\Domain\Item\Attribute\Repository\AttributeDefinitionRepository;

/**
 * PDO-backed {@see AttributeDefinitionRepository}.
 *
 * SQL is portable across MariaDB and SQLite. `enum_values` is
 * JSON-encoded on write and decoded on read; both adapters
 * round-trip strings the same way for the shapes we use.
 */
final class PdoAttributeDefinitionRepository implements AttributeDefinitionRepository
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly DateTimeZone $timezone = new DateTimeZone('UTC'),
    ) {
    }

    public function findById(int $id): ?AttributeDefinition
    {
        $stmt = $this->pdo->prepare('SELECT * FROM item_attribute_definition WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    public function findByCode(AttributeCode $code): ?AttributeDefinition
    {
        $stmt = $this->pdo->prepare('SELECT * FROM item_attribute_definition WHERE code = :code');
        $stmt->execute(['code' => $code->toString()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    public function listOrdered(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM item_attribute_definition ORDER BY sort_order ASC, code ASC',
        );
        if ($stmt === false) {
            return [];
        }

        return array_map(
            fn (array $row): AttributeDefinition => $this->hydrate($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    public function save(AttributeDefinition $definition): AttributeDefinition
    {
        $now      = (new DateTimeImmutable('now', $this->timezone))->format('Y-m-d H:i:s');
        $existing = $this->findById($definition->id);

        $enumJson = $definition->enumValues === null
            ? null
            : (string) json_encode($definition->enumValues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($existing === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO item_attribute_definition (id, code, label_en, label_ja, '.
                'value_type, unit, required, enum_values, validation_regex, sort_order, '.
                'created_at, updated_at) VALUES (:id, :code, :label_en, :label_ja, '.
                ':type, :unit, :req, :enums, :regex, :sort, :ca, :ua)',
            );
            $stmt->bindValue('id', $definition->id, PDO::PARAM_INT);
            $stmt->bindValue('code', $definition->code->toString());
            $stmt->bindValue('label_en', $definition->labelEn);
            $stmt->bindValue('label_ja', $definition->labelJa);
            $stmt->bindValue('type', $definition->valueType->value);
            $stmt->bindValue('unit', $definition->unit);
            $stmt->bindValue('req', $definition->required ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue('enums', $enumJson);
            $stmt->bindValue('regex', $definition->validationRegex);
            $stmt->bindValue('sort', $definition->sortOrder, PDO::PARAM_INT);
            $stmt->bindValue('ca', $definition->createdAt->setTimezone($this->timezone)->format('Y-m-d H:i:s'));
            $stmt->bindValue('ua', $now);
            $stmt->execute();
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE item_attribute_definition SET code = :code, label_en = :label_en, '.
                'label_ja = :label_ja, value_type = :type, unit = :unit, required = :req, '.
                'enum_values = :enums, validation_regex = :regex, sort_order = :sort, '.
                'updated_at = :ua WHERE id = :id',
            );
            $stmt->bindValue('id', $definition->id, PDO::PARAM_INT);
            $stmt->bindValue('code', $definition->code->toString());
            $stmt->bindValue('label_en', $definition->labelEn);
            $stmt->bindValue('label_ja', $definition->labelJa);
            $stmt->bindValue('type', $definition->valueType->value);
            $stmt->bindValue('unit', $definition->unit);
            $stmt->bindValue('req', $definition->required ? 1 : 0, PDO::PARAM_INT);
            $stmt->bindValue('enums', $enumJson);
            $stmt->bindValue('regex', $definition->validationRegex);
            $stmt->bindValue('sort', $definition->sortOrder, PDO::PARAM_INT);
            $stmt->bindValue('ua', $now);
            $stmt->execute();
        }

        $reread = $this->findById($definition->id);
        if ($reread === null) {
            throw new \RuntimeException(sprintf(
                'PdoAttributeDefinitionRepository::save lost row %d after write.',
                $definition->id,
            ));
        }

        return $reread;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM item_attribute_definition WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): AttributeDefinition
    {
        $enumValues = null;
        if (isset($row['enum_values']) && is_string($row['enum_values']) && $row['enum_values'] !== '') {
            $decoded = json_decode($row['enum_values'], associative: true);
            if (is_array($decoded)) {
                /** @var list<string> $list */
                $list       = array_values(array_map('strval', $decoded));
                $enumValues = $list;
            }
        }

        return new AttributeDefinition(
            id: (int) $row['id'],
            code: new AttributeCode((string) $row['code']),
            labelEn: (string) $row['label_en'],
            labelJa: (string) $row['label_ja'],
            valueType: AttributeValueType::from((string) $row['value_type']),
            unit: isset($row['unit']) && is_string($row['unit']) ? $row['unit'] : null,
            required: (int) $row['required'] === 1,
            enumValues: $enumValues,
            validationRegex: isset($row['validation_regex']) && is_string($row['validation_regex'])
                ? $row['validation_regex']
                : null,
            sortOrder: (int) $row['sort_order'],
            createdAt: new DateTimeImmutable((string) $row['created_at'], $this->timezone),
            updatedAt: new DateTimeImmutable((string) $row['updated_at'], $this->timezone),
        );
    }
}
