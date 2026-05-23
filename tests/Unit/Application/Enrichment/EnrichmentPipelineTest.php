<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Application\Enrichment;

use PHPUnit\Framework\TestCase;
use Saso\Application\Enrichment\DraftData;
use Saso\Application\Enrichment\EnrichmentPipeline;
use Saso\Application\Enrichment\Step\AiVisionStepInterface;
use Saso\Application\Enrichment\Step\IsbnLookupStepInterface;
use Saso\Application\Enrichment\Step\JanLookupStepInterface;
use Saso\Application\Enrichment\Step\KeywordLookupStepInterface;
use Saso\Application\Enrichment\Step\MergeStep;

final class EnrichmentPipelineTest extends TestCase
{
    private EnrichmentPipeline $pipeline;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pipeline = new EnrichmentPipeline(
            new TestIsbnLookupStep(),
            new TestJanLookupStep(),
            new TestAiVisionStep(),
            new TestKeywordLookupStep(),
            new MergeStep(),
        );
    }

    public function testJan8FoodItemBarcode(): void
    {
        $jan8 = '49012345';

        TestJanLookupStep::$data = [
            'jan_code' => $jan8,
            'item_name' => 'コーヒー飲料',
            'manufacturer' => 'テスト飲料会社',
        ];

        $draft = new DraftData(
            id: 1,
            imagePath: '/tmp/test.jpg',
            barcodeHint: $jan8,
            userData: null,
        );

        $result = $this->pipeline->run($draft);

        self::assertNotEmpty($result['item_name']);
        self::assertSame('コーヒー飲料', $result['item_name']);
        self::assertNotEmpty($result['manufacturer']);
        self::assertSame('テスト飲料会社', $result['manufacturer']);
        self::assertSame($jan8, $result['jan_code']);
    }

    public function testJan13GeneralGoodsBarcode(): void
    {
        $jan13 = '4901234567890';

        TestJanLookupStep::$data = [
            'jan_code' => $jan13,
            'item_name' => '電池',
            'manufacturer' => 'パワー電子',
            'description' => '単3形乾電池、アルカリ性',
        ];

        TestAiVisionStep::$data = [
            'category_hint' => '電子機器',
        ];

        $draft = new DraftData(
            id: 2,
            imagePath: '/tmp/test.jpg',
            barcodeHint: $jan13,
            userData: null,
        );

        $result = $this->pipeline->run($draft);

        self::assertSame('電池', $result['item_name']);
        self::assertSame('パワー電子', $result['manufacturer']);
        self::assertSame('単3形乾電池、アルカリ性', $result['description']);
        self::assertSame($jan13, $result['jan_code']);
        self::assertSame('電子機器', $result['category_hint']);
    }

    public function testIsbn13With978Prefix(): void
    {
        $isbn = '9784003100011';

        TestIsbnLookupStep::$data = [
            'isbn' => $isbn,
            'item_name' => 'きつねとぶどう',
            'manufacturer' => '岩波書店',
            'description' => 'イソップ寓話の名作',
        ];

        $draft = new DraftData(
            id: 3,
            imagePath: '/tmp/test.jpg',
            barcodeHint: $isbn,
            userData: null,
        );

        $result = $this->pipeline->run($draft);

        self::assertSame('きつねとぶどう', $result['item_name']);
        self::assertSame('岩波書店', $result['manufacturer']);
        self::assertSame($isbn, $result['isbn']);
    }

    public function testIsbn13With979Prefix(): void
    {
        $isbn = '9791032309667';

        TestIsbnLookupStep::$data = [
            'isbn' => $isbn,
            'item_name' => 'テスト書籍',
            'manufacturer' => 'テスト出版社',
        ];

        TestKeywordLookupStep::$data = [
            'description' => 'テストの説明が追加されました',
        ];

        $draft = new DraftData(
            id: 4,
            imagePath: '/tmp/test.jpg',
            barcodeHint: $isbn,
            userData: null,
        );

        $result = $this->pipeline->run($draft);

        self::assertSame('テスト書籍', $result['item_name']);
        self::assertSame('テスト出版社', $result['manufacturer']);
        self::assertSame($isbn, $result['isbn']);
        self::assertSame('テストの説明が追加されました', $result['description']);
    }

    public function testImageOnlyNoBarcode(): void
    {
        TestAiVisionStep::$data = [
            'item_name' => 'テスト商品',
            'manufacturer' => 'テストメーカー',
            'description' => 'AI により抽出された説明',
            'category_hint' => '商品',
        ];

        $draft = new DraftData(
            id: 5,
            imagePath: '/tmp/test.jpg',
            barcodeHint: null,
            userData: null,
        );

        $result = $this->pipeline->run($draft);

        self::assertSame('テスト商品', $result['item_name']);
        self::assertSame('テストメーカー', $result['manufacturer']);
        self::assertSame('AI により抽出された説明', $result['description']);
        self::assertSame('商品', $result['category_hint']);
    }

    public function testAiDetectsBarcode(): void
    {
        TestAiVisionStep::$data = [
            'item_name' => 'テスト書籍',
            'manufacturer' => 'テスト出版社',
            'description' => 'テストの説明',
            'jan_code' => '4901234567890',
            'category_hint' => '書籍',
        ];

        $draft = new DraftData(
            id: 6,
            imagePath: '/tmp/test.jpg',
            barcodeHint: null,
            userData: null,
        );

        $result = $this->pipeline->run($draft);

        self::assertSame('テスト書籍', $result['item_name']);
        self::assertSame('4901234567890', $result['jan_code']);
    }

    public function testElectronicsNonFoodJan(): void
    {
        $jan = '4514110047129';

        TestJanLookupStep::$data = [
            'jan_code' => $jan,
            'item_name' => 'テスト電子機器',
            'manufacturer' => 'テック会社',
        ];

        TestAiVisionStep::$data = [
            'category_hint' => '電子機器',
            'description' => 'AI による商品説明',
        ];

        $draft = new DraftData(
            id: 7,
            imagePath: '/tmp/test.jpg',
            barcodeHint: $jan,
            userData: null,
        );

        $result = $this->pipeline->run($draft);

        self::assertSame('テスト電子機器', $result['item_name']);
        self::assertSame('テック会社', $result['manufacturer']);
        self::assertSame('電子機器', $result['category_hint']);
        self::assertSame('AI による商品説明', $result['description']);
    }

    public function testUserFieldsAlwaysWin(): void
    {
        $jan = '4901234567890';

        TestJanLookupStep::$data = [
            'jan_code' => $jan,
            'item_name' => 'JAN から取得した商品名',
            'manufacturer' => 'JAN から取得したメーカー',
        ];

        TestAiVisionStep::$data = [
            'item_name' => 'AI で抽出した商品名',
            'manufacturer' => 'AI で抽出したメーカー',
            'description' => 'AI で生成した説明',
            'category_hint' => 'カテゴリ',
        ];

        $userData = [
            'item_name' => 'ユーザが入力した商品名',
        ];

        $draft = new DraftData(
            id: 8,
            imagePath: '/tmp/test.jpg',
            barcodeHint: $jan,
            userData: $userData,
        );

        $result = $this->pipeline->run($draft);

        self::assertSame('ユーザが入力した商品名', $result['item_name']);
        self::assertSame('JAN から取得したメーカー', $result['manufacturer']);
        self::assertSame('AI で生成した説明', $result['description']);
    }

    public function testNullAssistantNoAiKey(): void
    {
        $jan = '4901234567890';

        TestJanLookupStep::$data = [
            'jan_code' => $jan,
            'item_name' => 'JAN 商品名',
            'manufacturer' => 'JAN メーカー',
            'description' => 'JAN 説明',
        ];

        TestAiVisionStep::$data = [];

        $draft = new DraftData(
            id: 9,
            imagePath: '/tmp/test.jpg',
            barcodeHint: $jan,
            userData: null,
        );

        $result = $this->pipeline->run($draft);

        self::assertSame('JAN 商品名', $result['item_name']);
        self::assertSame('JAN メーカー', $result['manufacturer']);
        self::assertSame('JAN 説明', $result['description']);
        self::assertSame($jan, $result['jan_code']);
    }

    public function testAllSourcesEmpty(): void
    {
        $draft = new DraftData(
            id: 10,
            imagePath: '/tmp/test.jpg',
            barcodeHint: null,
            userData: null,
        );

        $result = $this->pipeline->run($draft);

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        TestIsbnLookupStep::$data = [];
        TestJanLookupStep::$data = [];
        TestAiVisionStep::$data = [];
        TestKeywordLookupStep::$data = [];
    }
}

final class TestIsbnLookupStep implements IsbnLookupStepInterface
{
    /** @var array<string, mixed> */
    public static array $data = [];

    /**
     * @return array<string, mixed>
     */
    public function run(?string $_barcodeHint): array
    {
        return self::$data;
    }
}

final class TestJanLookupStep implements JanLookupStepInterface
{
    /** @var array<string, mixed> */
    public static array $data = [];

    /**
     * @return array<string, mixed>
     */
    public function run(?string $_barcodeHint): array
    {
        return self::$data;
    }
}

final class TestAiVisionStep implements AiVisionStepInterface
{
    /** @var array<string, mixed> */
    public static array $data = [];

    /**
     * @param array<string, mixed> $_existing
     *
     * @return array<string, mixed>
     */
    public function run(string $_imagePath, ?string $_barcodeHint, array $_existing): array
    {
        return self::$data;
    }

    /**
     * @param array<string, mixed> $_existing
     * @param list<string> $_missingFields
     *
     * @return array<string, mixed>
     */
    public function runForFields(
        string $_imagePath,
        ?string $_barcodeHint,
        array $_existing,
        array $_missingFields,
    ): array {
        return self::$data;
    }
}

final class TestKeywordLookupStep implements KeywordLookupStepInterface
{
    /** @var array<string, mixed> */
    public static array $data = [];

    /**
     * @param array<string, mixed> $_existing
     *
     * @return array<string, mixed>
     */
    public function run(array $_existing): array
    {
        return self::$data;
    }
}
