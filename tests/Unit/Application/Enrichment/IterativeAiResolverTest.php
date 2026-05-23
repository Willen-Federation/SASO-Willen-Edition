<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Application\Enrichment;

use PHPUnit\Framework\TestCase;
use Saso\Application\Enrichment\IterativeAiResolver;
use Saso\Application\Enrichment\Step\AiVisionStepInterface;
use Saso\Application\Enrichment\Step\MergeStep;

final class IterativeAiResolverTest extends TestCase
{
    public function testSingleCallWhenAllFieldsFilledImmediately(): void
    {
        $ai = new RecordingAiVisionStep([
            'first' => [
                // All TARGET_KEYS are answered — null is treated as an explicit
                // AI verdict ("looked, no data") so no retry should happen.
                'item_name'     => 'カメラ',
                'description'   => '高画質',
                'category_hint' => '電子機器',
                'jan_code'      => '4901234567890',
                'isbn'          => null,
            ],
        ]);

        $resolver = new IterativeAiResolver($ai, new MergeStep(), maxAttempts: 3);
        $result = $resolver->run([], '/tmp/img.jpg', null);

        self::assertSame(1, $ai->runCalls);
        self::assertSame(0, $ai->runForFieldsCalls);
        self::assertSame('カメラ', $result['item_name']);
        self::assertSame('4901234567890', $result['jan_code']);
    }

    public function testIteratesWhenFieldsRemainMissing(): void
    {
        // Each retry brings in one *additional* key. Keys absent from the
        // overlay (rather than explicitly null) keep counting as missing.
        $ai = new RecordingAiVisionStep([
            'first'  => ['item_name' => '商品'],
            'retry1' => ['description' => '商品説明'],
            'retry2' => ['category_hint' => 'カテゴリ'],
        ]);

        $resolver = new IterativeAiResolver($ai, new MergeStep(), maxAttempts: 3);
        $result = $resolver->run([], '/tmp/img.jpg', null);

        self::assertSame(1, $ai->runCalls);
        self::assertSame(2, $ai->runForFieldsCalls);
        self::assertSame('商品', $result['item_name']);
        self::assertSame('商品説明', $result['description']);
        self::assertSame('カテゴリ', $result['category_hint']);

        self::assertSame(
            ['description', 'category_hint', 'jan_code', 'isbn'],
            $ai->firstMissingFields,
        );
        self::assertSame(
            ['category_hint', 'jan_code', 'isbn'],
            $ai->secondMissingFields,
        );
    }

    public function testStopsEarlyWhenAiReturnsEmpty(): void
    {
        $ai = new RecordingAiVisionStep([
            'first' => [],
        ]);

        $resolver = new IterativeAiResolver($ai, new MergeStep(), maxAttempts: 3);
        $result = $resolver->run([], '/tmp/img.jpg', null);

        self::assertSame(1, $ai->runCalls);
        self::assertSame(0, $ai->runForFieldsCalls);
        self::assertSame([], $result);
    }

    public function testRespectsMaxAttemptCap(): void
    {
        $ai = new RecordingAiVisionStep([
            'first'  => ['item_name' => '商品'],
            'retry1' => ['description' => '説明'],
            'retry2' => ['category_hint' => 'カテゴリ'],
            'retry3' => ['jan_code' => '12345678'],
        ]);

        $resolver = new IterativeAiResolver($ai, new MergeStep(), maxAttempts: 2);
        $result = $resolver->run([], '/tmp/img.jpg', null);

        self::assertSame(1, $ai->runCalls);
        self::assertSame(1, $ai->runForFieldsCalls);
        self::assertSame('商品', $result['item_name']);
        self::assertSame('説明', $result['description']);
        self::assertArrayNotHasKey('category_hint', $result);
    }

    public function testBarcodeFieldsLockedOutOfRetry(): void
    {
        $ai = new RecordingAiVisionStep([
            'first'  => ['item_name' => '本'],
            'retry1' => [
                'description'   => '説明',
                'category_hint' => '書籍',
                'isbn'          => '9999999999999',
            ],
        ]);

        $base = [
            'jan_code' => '4901234567890',
            'isbn'     => '9784003100011',
        ];

        $resolver = new IterativeAiResolver($ai, new MergeStep(), maxAttempts: 3);
        $result = $resolver->run($base, '/tmp/img.jpg', '9784003100011');

        // jan_code / isbn never enter the missing list, so the AI's isbn
        // overlay must be discarded and the barcode lookup value preserved.
        self::assertSame('9784003100011', $result['isbn']);
        self::assertSame('4901234567890', $result['jan_code']);
        self::assertSame('本', $result['item_name']);

        // After the 1st call, item_name is filled, so the 1st runForFields
        // call only asks about description and category_hint (jan_code/isbn
        // are barcode-locked).
        self::assertSame(
            ['description', 'category_hint'],
            $ai->firstMissingFields,
        );
    }

    public function testRespectsUserProtectedFields(): void
    {
        $ai = new RecordingAiVisionStep([
            'first' => [
                'item_name'   => 'AI上書き',
                'description' => 'AI説明',
            ],
        ]);

        $base = ['item_name' => 'ユーザ入力'];
        $resolver = new IterativeAiResolver($ai, new MergeStep(), maxAttempts: 3);
        $result = $resolver->run($base, '/tmp/img.jpg', null, ['item_name']);

        self::assertSame('ユーザ入力', $result['item_name']);
        self::assertSame('AI説明', $result['description']);
    }
}

final class RecordingAiVisionStep implements AiVisionStepInterface
{
    public int $runCalls = 0;
    public int $runForFieldsCalls = 0;

    /** @var list<string> */
    public array $firstMissingFields = [];

    /** @var list<string> */
    public array $secondMissingFields = [];

    /**
     * @param array<string, array<string, mixed>> $scripted slot keys: first, retry1, retry2, retry3...
     */
    public function __construct(
        private readonly array $scripted,
    ) {
    }

    public function run(string $imagePath, ?string $barcodeHint, array $existing): array
    {
        $this->runCalls++;

        return $this->scripted['first'] ?? [];
    }

    public function runForFields(
        string $imagePath,
        ?string $barcodeHint,
        array $existing,
        array $missingFields,
    ): array {
        $this->runForFieldsCalls++;
        if ($this->runForFieldsCalls === 1) {
            $this->firstMissingFields = $missingFields;
        } elseif ($this->runForFieldsCalls === 2) {
            $this->secondMissingFields = $missingFields;
        }

        return $this->scripted['retry'.$this->runForFieldsCalls] ?? [];
    }
}
