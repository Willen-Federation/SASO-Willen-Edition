<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Application\Enrichment;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Saso\Application\Enrichment\Step\AiVisionStep;
use Saso\Domain\Ai\AiAssistant;
use Saso\Domain\Ai\AiUsage;
use Saso\Domain\Ai\ChatRequest;
use Saso\Domain\Ai\ChatResponse;
use Saso\Domain\Ai\EmbeddingRequest;
use Saso\Domain\Ai\EmbeddingResponse;
use Saso\Domain\Ai\Exception\AiProviderNotConfiguredException;
use Saso\Domain\Ai\Exception\AiResponseMalformedException;
use Saso\Domain\Ai\ImageDescriptionResponse;
use Saso\Domain\Ai\ImageRequest;
use Saso\Domain\Ai\StructuredExtractionRequest;
use Saso\Domain\Ai\StructuredExtractionResponse;
use Saso\Domain\Feature\FeatureFlag;
use Saso\Domain\Feature\FeatureKey;
use Saso\Domain\Feature\Repository\FeatureFlagRepository;

final class AiVisionStepTest extends TestCase
{
    private const FLAG_KEY = 'ai.auto_judge';

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    private function makeFlag(bool $enabled): FeatureFlag
    {
        $now = new DateTimeImmutable();

        return new FeatureFlag(
            id: 1,
            key: new FeatureKey(self::FLAG_KEY),
            description: 'AI auto-judge',
            enabled: $enabled,
            rolloutPercent: 100,
            conditions: null,
            errorThreshold: 0,
            errorWindowMinutes: 1,
            autoDisabledAt: null,
            autoDisableReason: null,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    /** @param FeatureFlag|null $flag */
    private function flagRepo(?FeatureFlag $flag): FeatureFlagRepository
    {
        return new class ($flag) implements FeatureFlagRepository {
            public function __construct(private readonly ?FeatureFlag $flag)
            {
            }

            public function findByKey(FeatureKey $key): ?FeatureFlag
            {
                return $this->flag;
            }

            public function findById(int $id): ?FeatureFlag
            {
                return null;
            }

            /** @return list<FeatureFlag> */
            public function listAll(): array
            {
                return [];
            }

            public function nextId(): int
            {
                return 1;
            }

            public function save(FeatureFlag $flag): FeatureFlag
            {
                return $flag;
            }

            public function delete(int $id): void
            {
            }
        };
    }

    private function nullAi(): AiAssistant
    {
        return new class () implements AiAssistant {
            public function chatComplete(ChatRequest $req): ChatResponse
            {
                throw AiProviderNotConfiguredException::for('mock', 'chatComplete');
            }

            public function extractStructured(StructuredExtractionRequest $req): StructuredExtractionResponse
            {
                throw AiProviderNotConfiguredException::for('mock', 'extractStructured');
            }

            public function embed(EmbeddingRequest $req): EmbeddingResponse
            {
                throw AiProviderNotConfiguredException::for('mock', 'embed');
            }

            public function describeImage(ImageRequest $req): ImageDescriptionResponse
            {
                throw AiProviderNotConfiguredException::for('mock', 'describeImage');
            }
        };
    }

    // ---------------------------------------------------------------------------
    // Flag-gate tests (no image file needed — returns before touching filesystem)
    // ---------------------------------------------------------------------------

    public function testFlagAbsentReturnsEmpty(): void
    {
        $step = new AiVisionStep($this->nullAi(), $this->flagRepo(null));

        self::assertSame([], $step->run('/any/path.jpg', null, []));
    }

    public function testFlagDisabledReturnsEmpty(): void
    {
        $step = new AiVisionStep($this->nullAi(), $this->flagRepo($this->makeFlag(false)));

        self::assertSame([], $step->run('/any/path.jpg', null, []));
    }

    // ---------------------------------------------------------------------------
    // Flag ON — AI not configured
    // ---------------------------------------------------------------------------

    public function testFlagEnabledButNoProviderReturnsEmpty(): void
    {
        $step = new AiVisionStep($this->nullAi(), $this->flagRepo($this->makeFlag(true)));

        // Image must be readable; create a 1-byte temp file
        $tmp = tempnam(sys_get_temp_dir(), 'ai_test_');
        assert($tmp !== false);
        file_put_contents($tmp, 'x');

        try {
            $result = $step->run($tmp, null, []);
            self::assertSame([], $result);
        } finally {
            unlink($tmp);
        }
    }

    // ---------------------------------------------------------------------------
    // Flag ON — AI returns data
    // ---------------------------------------------------------------------------

    public function testFlagEnabledAiReturnsData(): void
    {
        $expected = [
            'item_name'     => 'テスト商品',
            'manufacturer'  => 'テストメーカー',
            'description'   => 'テスト説明',
            'category_hint' => '電子機器',
        ];

        $mockAi = new class ($expected) implements AiAssistant {
            /** @param array<string, mixed> $data */
            public function __construct(private readonly array $data)
            {
            }

            public function chatComplete(ChatRequest $req): ChatResponse
            {
                throw AiProviderNotConfiguredException::for('mock', 'chatComplete');
            }

            public function extractStructured(StructuredExtractionRequest $req): StructuredExtractionResponse
            {
                return new StructuredExtractionResponse($this->data, new AiUsage(), 'mock-model');
            }

            public function embed(EmbeddingRequest $req): EmbeddingResponse
            {
                throw AiProviderNotConfiguredException::for('mock', 'embed');
            }

            public function describeImage(ImageRequest $req): ImageDescriptionResponse
            {
                throw AiProviderNotConfiguredException::for('mock', 'describeImage');
            }
        };

        $step = new AiVisionStep($mockAi, $this->flagRepo($this->makeFlag(true)));

        $tmp = tempnam(sys_get_temp_dir(), 'ai_test_');
        assert($tmp !== false);
        file_put_contents($tmp, 'fake-image-bytes');

        try {
            $result = $step->run($tmp, '4901234567890', []);
            self::assertSame($expected, $result);
        } finally {
            unlink($tmp);
        }
    }

    // ---------------------------------------------------------------------------
    // Edge: unreadable path with flag ON
    // ---------------------------------------------------------------------------

    public function testFlagEnabledUnreadablePathReturnsEmpty(): void
    {
        $step = new AiVisionStep($this->nullAi(), $this->flagRepo($this->makeFlag(true)));

        self::assertSame([], $step->run('/nonexistent/path.jpg', null, []));
    }

    public function testMalformedAiResponseReturnsEmpty(): void
    {
        $mockAi = new class () implements AiAssistant {
            public function chatComplete(ChatRequest $req): ChatResponse
            {
                throw AiProviderNotConfiguredException::for('mock', 'chatComplete');
            }

            public function extractStructured(StructuredExtractionRequest $req): StructuredExtractionResponse
            {
                throw AiResponseMalformedException::for('mock', 'invalid JSON');
            }

            public function embed(EmbeddingRequest $req): EmbeddingResponse
            {
                throw AiProviderNotConfiguredException::for('mock', 'embed');
            }

            public function describeImage(ImageRequest $req): ImageDescriptionResponse
            {
                throw AiProviderNotConfiguredException::for('mock', 'describeImage');
            }
        };

        $step = new AiVisionStep($mockAi, $this->flagRepo($this->makeFlag(true)));

        $tmp = tempnam(sys_get_temp_dir(), 'ai_test_');
        assert($tmp !== false);
        file_put_contents($tmp, 'fake-image-bytes');

        try {
            self::assertSame([], $step->run($tmp, null, []));
        } finally {
            unlink($tmp);
        }
    }
}
