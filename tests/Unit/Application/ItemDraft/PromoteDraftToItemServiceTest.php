<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Application\ItemDraft;

use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Saso\Application\Category\CategoryHintResolver;
use Saso\Application\ItemDraft\PromoteDraftToItemService;
use Saso\Domain\Category\Category;
use Saso\Domain\Category\CategoryCode;
use Saso\Domain\Category\Repository\CategoryRepository;
use Saso\Domain\ItemDraft\ItemDraft;
use Saso\Domain\ItemDraft\ItemDraftStatus;
use Saso\Domain\ItemDraft\Repository\ItemDraftRepository;

final class PromoteDraftToItemServiceTest extends TestCase
{
    private PDO $pdo;
    private FakeItemDraftRepository $drafts;
    private CategoryHintResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(
            'CREATE TABLE item (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                category_id INTEGER NOT NULL,
                jan_code TEXT,
                isbn TEXT,
                label_code TEXT,
                note TEXT,
                price INTEGER NOT NULL DEFAULT 0,
                stock INTEGER NOT NULL DEFAULT 0,
                status TEXT NOT NULL DEFAULT "active",
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )',
        );
        $this->pdo->exec(
            'CREATE TABLE item_draft (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                promoted_item_id INTEGER
            )',
        );

        $this->drafts = new FakeItemDraftRepository();
        $this->resolver = new CategoryHintResolver(new SingleCategoryRepo([
            $this->category(1, '電子機器'),
            $this->category(2, '書籍'),
        ]));
    }

    public function testPromoteInsertsItemAndMarksDraftConfirmed(): void
    {
        $draft = $this->makeDraft(1);
        $service = new PromoteDraftToItemService($this->pdo, $this->drafts, $this->resolver);

        $itemId = $service->promote($draft, [
            'item_name'     => 'カメラ',
            'description'   => '高画質カメラ',
            'manufacturer'  => 'メーカーA',
            'category_hint' => '電子機器',
            'jan_code'      => '4901234567890',
            'price'         => 12000,
        ]);

        $row = $this->pdo->query('SELECT * FROM item WHERE id = '.$itemId)->fetch(PDO::FETCH_ASSOC);

        self::assertSame('カメラ', $row['name']);
        self::assertSame(1, (int) $row['category_id']);
        self::assertSame('4901234567890', $row['jan_code']);
        self::assertSame(12000, (int) $row['price']);
        self::assertSame('active', $row['status']);
        self::assertNotNull($row['note']);
        self::assertStringContainsString('高画質カメラ', (string) $row['note']);
        self::assertStringContainsString('メーカーA', (string) $row['note']);

        self::assertSame($itemId, $this->drafts->lastPromotedItemId);
        self::assertSame(1, $this->drafts->lastPromotedDraftId);
    }

    public function testPromoteIsIdempotentViaDraftPromotedItemId(): void
    {
        $draft = $this->makeDraft(7, promotedItemId: 999);
        $service = new PromoteDraftToItemService($this->pdo, $this->drafts, $this->resolver);

        $itemId = $service->promote($draft, [
            'item_name'     => 'カメラ',
            'category_hint' => '電子機器',
        ]);

        self::assertSame(999, $itemId);
        self::assertSame(0, $this->countItems(), 'INSERT must NOT run when draft is already promoted.');
    }

    public function testPromoteIsIdempotentViaDatabaseColumn(): void
    {
        $this->pdo->exec('INSERT INTO item_draft (id, promoted_item_id) VALUES (5, 777)');
        $draft = $this->makeDraft(5);
        $service = new PromoteDraftToItemService($this->pdo, $this->drafts, $this->resolver);

        $itemId = $service->promote($draft, [
            'item_name'     => '本',
            'category_hint' => '書籍',
        ]);

        self::assertSame(777, $itemId);
        self::assertSame(0, $this->countItems());
    }

    public function testRefusesWhenItemNameMissing(): void
    {
        $draft = $this->makeDraft(2);
        $service = new PromoteDraftToItemService($this->pdo, $this->drafts, $this->resolver);

        try {
            $service->promote($draft, ['category_hint' => '電子機器']);
            self::fail('Expected RuntimeException.');
        } catch (RuntimeException) {
            // expected
        }

        self::assertSame(ItemDraftStatus::Failed, $this->drafts->lastStatus);
        self::assertNotNull($this->drafts->lastError);
        self::assertSame(0, $this->countItems());
    }

    public function testRefusesWhenCategoryTableEmpty(): void
    {
        $emptyResolver = new CategoryHintResolver(new SingleCategoryRepo([]));
        $service = new PromoteDraftToItemService($this->pdo, $this->drafts, $emptyResolver);
        $draft = $this->makeDraft(3);

        try {
            $service->promote($draft, [
                'item_name'     => 'X',
                'category_hint' => '電子機器',
            ]);
            self::fail('Expected RuntimeException.');
        } catch (RuntimeException) {
            // expected
        }

        self::assertSame(ItemDraftStatus::Failed, $this->drafts->lastStatus);
        self::assertSame(0, $this->countItems());
    }

    private function makeDraft(int $id, ?int $promotedItemId = null): ItemDraft
    {
        $now = new DateTimeImmutable();

        return new ItemDraft(
            id: $id,
            imagePath: 'uploads/item_drafts/x.jpg',
            barcodeHint: null,
            userData: null,
            aiResult: null,
            status: ItemDraftStatus::Processing,
            processingStartedAt: $now,
            errorDetail: null,
            createdBy: null,
            createdAt: $now,
            updatedAt: $now,
            autoRegister: true,
            promotedItemId: $promotedItemId,
        );
    }

    private function category(int $id, string $nameJa): Category
    {
        $now = new DateTimeImmutable();

        return new Category(
            id: $id,
            code: new CategoryCode('CAT'.$id),
            nameEn: 'Cat'.$id,
            nameJa: $nameJa,
            parentId: null,
            depth: 0,
            sortOrder: $id,
            description: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    private function countItems(): int
    {
        $value = $this->pdo->query('SELECT COUNT(*) FROM item')->fetchColumn();

        return (int) $value;
    }
}

final class FakeItemDraftRepository implements ItemDraftRepository
{
    public ?int $lastPromotedDraftId = null;
    public ?int $lastPromotedItemId  = null;
    public ?ItemDraftStatus $lastStatus = null;
    public ?string $lastError = null;

    public function __construct()
    {
    }

    public function findById(int $id): ?ItemDraft
    {
        return null;
    }

    public function findByStatus(ItemDraftStatus $status, int $limit = 50): array
    {
        return [];
    }

    public function save(ItemDraft $draft): void
    {
    }

    public function create(
        string $imagePath,
        ?string $barcodeHint,
        ?array $userData,
        ?int $createdBy,
        bool $autoRegister = false,
    ): int {
        return 0;
    }

    public function updateStatus(int $id, ItemDraftStatus $status, ?string $errorDetail = null): void
    {
        $this->lastStatus = $status;
        $this->lastError  = $errorDetail;
    }

    public function updateAiResult(int $id, array $aiResult, ItemDraftStatus $status): void
    {
        $this->lastStatus = $status;
    }

    public function markProcessing(int $id): void
    {
    }

    public function markPromoted(int $id, int $itemId): void
    {
        $this->lastPromotedDraftId = $id;
        $this->lastPromotedItemId  = $itemId;
        $this->lastStatus = ItemDraftStatus::Confirmed;
    }
}

final class SingleCategoryRepo implements CategoryRepository
{
    /**
     * @param list<Category> $categories
     */
    public function __construct(private array $categories)
    {
    }

    public function findById(int $id): ?Category
    {
        foreach ($this->categories as $c) {
            if ($c->id === $id) {
                return $c;
            }
        }

        return null;
    }

    public function findByCode(CategoryCode $code): ?Category
    {
        return null;
    }

    public function listRoots(): array
    {
        return array_values(array_filter($this->categories, static fn (Category $c) => $c->isRoot()));
    }

    public function listChildrenOf(int $parentId): array
    {
        return [];
    }

    public function listAll(): array
    {
        return $this->categories;
    }

    public function save(Category $category): Category
    {
        $this->categories[] = $category;

        return $category;
    }

    public function delete(int $id): void
    {
    }
}
