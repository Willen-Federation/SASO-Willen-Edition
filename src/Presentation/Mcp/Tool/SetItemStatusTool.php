<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PDO;
use Saso\Domain\Mcp\McpTool;

/**
 * MCP tool: `set_item_status`
 *
 * Changes the lifecycle status of an inventory item. Requires the
 * `status` column added by migration M6-I-004. Scope: `items:write`.
 *
 * Valid statuses:
 *   active       — Normal, in-use inventory
 *   archived     — Removed from active use but kept for history
 *   discontinued — No longer produced or stocked
 *   pending      — Awaiting confirmation / processing
 */
final class SetItemStatusTool implements McpTool
{
    private const VALID_STATUSES = ['active', 'archived', 'discontinued', 'pending'];

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function name(): string
    {
        return 'set_item_status';
    }

    public function description(): string
    {
        return 'Change the lifecycle status of an inventory item (active, archived, discontinued, pending).';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['id', 'status'],
            'properties' => [
                'id'     => [
                    'type'        => 'integer',
                    'minimum'     => 1,
                    'description' => 'Item ID.',
                ],
                'status' => [
                    'type'        => 'string',
                    'enum'        => self::VALID_STATUSES,
                    'description' => 'New lifecycle status.',
                ],
            ],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $id     = (int) ($input['id'] ?? 0);
        $status = (string) ($input['status'] ?? '');

        if ($id < 1) {
            throw new InvalidArgumentException('"id" must be a positive integer.');
        }

        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException(
                sprintf('"status" must be one of: %s.', implode(', ', self::VALID_STATUSES)),
            );
        }

        $now  = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE item SET status = :status, updated_at = :ua WHERE id = :id');
        $stmt->execute(['status' => $status, 'ua' => $now, 'id' => $id]);

        return [
            'updated' => $stmt->rowCount() > 0,
            'id'      => $id,
            'status'  => $status,
        ];
    }

    public function requiredScope(): ?string
    {
        return 'items:write';
    }
}
