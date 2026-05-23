<?php

declare(strict_types=1);

namespace Saso\Presentation\Mcp\Tool;

use InvalidArgumentException;
use PDO;
use RuntimeException;
use Saso\Application\Messaging\ProcessItemDraftDIContainer;
use Saso\Domain\Feature\Repository\FeatureFlagRepository;
use Saso\Domain\ItemDraft\Repository\ItemDraftRepository;
use Saso\Domain\Mcp\McpTool;
use Saso\Domain\Messaging\Message\ProcessItemDraft;
use Saso\Domain\Setting\SystemSettingService;
use Saso\Infrastructure\Messaging\MessageBusFactory;

/**
 * MCP tool: `auto_register_item`
 *
 * Queues a server-side image (already uploaded somewhere accessible to the
 * server, e.g. `uploads/item_drafts/foo.jpg`) for fully-automatic
 * registration: JAN/ISBN lookup → iterative AI vision → direct `item`
 * insert. The tool runs the enrichment+promotion pipeline synchronously
 * so the caller receives the resulting item id in the response.
 *
 * Scope: `items:write`.
 */
final class AutoRegisterItemTool implements McpTool
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ItemDraftRepository $drafts,
        private readonly SystemSettingService $settings,
        private readonly FeatureFlagRepository $flags,
    ) {
    }

    public function name(): string
    {
        return 'auto_register_item';
    }

    public function description(): string
    {
        return 'Register a new item from a product image using JAN/ISBN lookup and iterative AI vision. Requires the items:write scope and the ai.auto_register feature flag.';
    }

    public function inputSchema(): array
    {
        return [
            'type'       => 'object',
            'required'   => ['imagePath'],
            'properties' => [
                'imagePath' => [
                    'type'        => 'string',
                    'description' => 'Server-relative path to the uploaded image (e.g. "uploads/item_drafts/foo.jpg").',
                    'minLength'   => 1,
                    'maxLength'   => 500,
                ],
                'barcodeHint' => [
                    'type'        => ['string', 'null'],
                    'description' => 'Optional JAN/ISBN/EAN barcode string to seed the lookup pipeline.',
                    'maxLength'   => 50,
                ],
            ],
        ];
    }

    public function invoke(array $input, int $deviceId): array
    {
        $imagePath = trim((string) ($input['imagePath'] ?? ''));
        if ($imagePath === '') {
            throw new InvalidArgumentException('"imagePath" is required.');
        }

        $barcodeHint = isset($input['barcodeHint']) ? trim((string) $input['barcodeHint']) : null;
        if ($barcodeHint === '') {
            $barcodeHint = null;
        }

        $draftId = $this->drafts->create(
            imagePath: $imagePath,
            barcodeHint: $barcodeHint,
            userData: null,
            createdBy: $deviceId,
            autoRegister: true,
        );

        $handler = ProcessItemDraftDIContainer::createHandler(
            $this->drafts,
            $this->settings,
            $this->flags,
            $this->pdo,
        );

        $bus = MessageBusFactory::create([
            ProcessItemDraft::class => [$handler],
        ]);

        try {
            $bus->dispatch(new ProcessItemDraft($draftId));
        } catch (\Throwable $e) {
            throw new RuntimeException('auto_register_item: pipeline failed — '.$e->getMessage(), 0, $e);
        }

        $draft = $this->drafts->findById($draftId);
        if ($draft === null) {
            throw new RuntimeException('auto_register_item: draft vanished after processing.');
        }

        return [
            'draftId'     => $draftId,
            'status'      => $draft->status->value,
            'itemId'      => $draft->promotedItemId,
            'errorDetail' => $draft->errorDetail,
        ];
    }

    public function requiredScope(): ?string
    {
        return 'items:write';
    }
}
