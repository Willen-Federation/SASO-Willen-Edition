<?php

declare(strict_types=1);

namespace Saso\Domain\Item\Attribute\Repository;

use Saso\Domain\Item\Attribute\AttributeCode;
use Saso\Domain\Item\Attribute\AttributeDefinition;

/**
 * Read/write contract for `item_attribute_definition` rows
 * (cf. ADR 0011).
 *
 * `findByCode()` is the hot path — admin form rendering hits it
 * once per attribute per page. `listOrdered()` returns every row in
 * `sort_order ASC, code ASC` so the form layout is stable across
 * sessions.
 */
interface AttributeDefinitionRepository
{
    public function findById(int $id): ?AttributeDefinition;

    public function findByCode(AttributeCode $code): ?AttributeDefinition;

    /**
     * @return list<AttributeDefinition>
     */
    public function listOrdered(): array;

    public function save(AttributeDefinition $definition): AttributeDefinition;

    public function delete(int $id): void;
}
