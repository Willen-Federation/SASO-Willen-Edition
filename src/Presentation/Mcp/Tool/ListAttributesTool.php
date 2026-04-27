<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use Saso\Domain\Item\Attribute\AttributeDefinition;
use Saso\Domain\Item\Attribute\Repository\AttributeDefinitionRepository;
use Saso\Domain\Mcp\McpTool;

/**
 * MCP tool: `list_attributes`
 *
 * Returns all operator-defined item attribute definitions ordered by
 * sort_order. No authentication scope required — read-only discovery.
 */
final class ListAttributesTool implements McpTool
{
    public function __construct(
        private readonly AttributeDefinitionRepository $attributes,
    ) {
    }

    public function name(): string
    {
        return 'list_attributes';
    }

    public function description(): string
    {
        return 'List all custom item attribute definitions (the typed field schema for the item catalogue).';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => new \stdClass(),
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $defs = $this->attributes->listOrdered();

        return [
            'attributes' => array_map(self::serialize(...), $defs),
            'total'      => count($defs),
        ];
    }

    public function requiredScope(): ?string
    {
        return null;
    }

    /** @return array<string, mixed> */
    private static function serialize(AttributeDefinition $def): array
    {
        return [
            'id'              => $def->id,
            'code'            => $def->code->toString(),
            'labelEn'         => $def->labelEn,
            'labelJa'         => $def->labelJa,
            'valueType'       => $def->valueType->value,
            'unit'            => $def->unit,
            'required'        => $def->required,
            'enumValues'      => $def->enumValues,
            'validationRegex' => $def->validationRegex,
            'sortOrder'       => $def->sortOrder,
        ];
    }
}
