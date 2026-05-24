<?php

declare(strict_types=1);

namespace Saso\Application\Enrichment\Step;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Saso\Domain\Ai\AiAssistant;
use Saso\Domain\Ai\Exception\AiContentPolicyException;
use Saso\Domain\Ai\Exception\AiProviderNotConfiguredException;
use Saso\Domain\Ai\Exception\AiRateLimitedException;
use Saso\Domain\Ai\Exception\AiResponseMalformedException;
use Saso\Domain\Ai\Exception\AiUpstreamException;
use Saso\Domain\Ai\StructuredExtractionRequest;
use Saso\Domain\Feature\FeatureKey;
use Saso\Domain\Feature\Repository\FeatureFlagRepository;

final class AiVisionStep implements AiVisionStepInterface
{
    private const EXTRACTION_SCHEMA = [
        'type'       => 'object',
        'properties' => [
            'item_name'     => ['type' => 'string'],
            'manufacturer'  => ['type' => ['string', 'null']],
            'description'   => ['type' => 'string'],
            'jan_code'      => ['type' => ['string', 'null']],
            'isbn'          => ['type' => ['string', 'null']],
            'category_hint' => ['type' => 'string'],
            'price'         => ['type' => ['integer', 'null']],
        ],
        'required'   => ['item_name', 'manufacturer', 'description', 'category_hint'],
    ];

    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly AiAssistant $ai,
        private readonly FeatureFlagRepository $flags,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param array<string, mixed> $existing
     *
     * @return array<string, mixed>
     */
    public function run(string $imagePath, ?string $barcodeHint, array $existing): array
    {
        return $this->callAi($imagePath, $barcodeHint, self::EXTRACTION_SCHEMA, $this->buildPrompt($barcodeHint));
    }

    /**
     * @param array<string, mixed> $existing
     * @param list<string> $missingFields
     *
     * @return array<string, mixed>
     */
    public function runForFields(
        string $imagePath,
        ?string $barcodeHint,
        array $existing,
        array $missingFields,
    ): array {
        $missingFields = array_values(array_filter(
            $missingFields,
            static fn (string $k): bool => isset(self::EXTRACTION_SCHEMA['properties'][$k]),
        ));

        if ($missingFields === []) {
            return [];
        }

        $properties = [];
        foreach ($missingFields as $key) {
            $properties[$key] = self::EXTRACTION_SCHEMA['properties'][$key];
        }

        $required = array_values(array_intersect(
            self::EXTRACTION_SCHEMA['required'],
            $missingFields,
        ));

        $schema = [
            'type'       => 'object',
            'properties' => $properties,
            'required'   => $required,
        ];

        return $this->callAi(
            $imagePath,
            $barcodeHint,
            $schema,
            $this->buildRetryPrompt($barcodeHint, $missingFields),
        );
    }

    /**
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>
     */
    private function callAi(string $imagePath, ?string $barcodeHint, array $schema, string $instruction): array
    {
        $flag = $this->flags->findByKey(new FeatureKey('ai.auto_judge'));
        if ($flag === null || !$flag->enabled) {
            return [];
        }

        if (!is_readable($imagePath)) {
            return [];
        }

        $imageBytes = file_get_contents($imagePath);
        if ($imageBytes === false || $imageBytes === '') {
            return [];
        }

        $mime = $this->detectMimeType($imagePath);

        $request = new StructuredExtractionRequest(
            instruction: $instruction,
            sourceText: '',
            jsonSchema: $schema,
            imageBytes: $imageBytes,
            imageMimeType: $mime,
            maxTokens: 1024,
        );

        try {
            $response = $this->ai->extractStructured($request);

            return $response->data;
        } catch (AiProviderNotConfiguredException | AiRateLimitedException | AiUpstreamException | AiContentPolicyException | AiResponseMalformedException $e) {
            // Soft-fail so the enrichment pipeline can still benefit from
            // ISBN/JAN/keyword lookups when the AI provider is unavailable —
            // but log the reason so operators can diagnose silent-empty
            // drafts via `make logs`.
            $this->logger->warning('AiVisionStep: extraction failed, returning empty result', [
                'error_code'   => $e->errorCode()->value,
                'error_class'  => $e::class,
                'reason'       => $e->getMessage(),
                'image_path'   => $imagePath,
                'barcode_hint' => $barcodeHint,
            ]);

            return [];
        }
    }

    private function buildPrompt(?string $barcodeHint): string
    {
        $prompt = "この商品画像から製品情報を抽出してください。\n";

        $hint = $this->sanitiseBarcodeForPrompt($barcodeHint);
        if ($hint !== null) {
            $prompt .= "商品コード: {$hint}\n";
        }

        $prompt .= "バーコード/QRコードが見えれば jan_code または isbn として記録してください。\n";
        $prompt .= "商品名・メーカー・価格・説明を日本語で記述してください。\n";
        $prompt .= '不明な項目は null にしてください。';

        return $prompt;
    }

    /**
     * @param list<string> $missingFields
     */
    private function buildRetryPrompt(?string $barcodeHint, array $missingFields): string
    {
        $labels = [
            'item_name'     => '商品名',
            'manufacturer'  => 'メーカー',
            'description'   => '説明',
            'jan_code'      => 'JANコード',
            'isbn'          => 'ISBN',
            'category_hint' => 'カテゴリ',
            'price'         => '価格',
        ];

        $requested = array_map(
            static fn (string $k): string => $labels[$k] ?? $k,
            $missingFields,
        );

        $prompt = 'この商品画像から、以下の項目のみを再抽出してください: '.implode('、', $requested)."。\n";

        $hint = $this->sanitiseBarcodeForPrompt($barcodeHint);
        if ($hint !== null) {
            $prompt .= "商品コード: {$hint}\n";
        }

        $prompt .= "他の項目は出力しないでください。\n";
        $prompt .= '不明な項目は null にしてください。';

        return $prompt;
    }

    /**
     * Restrict the barcode hint to the small alphanumeric/hyphen set real
     * barcodes use before it is interpolated into the LLM system instruction.
     * The hint is user-controlled, so anything that looks like a control
     * character or natural-language sentence would be a prompt-injection
     * vector; we drop the value rather than passing it through.
     */
    private function sanitiseBarcodeForPrompt(?string $barcodeHint): ?string
    {
        if ($barcodeHint === null || $barcodeHint === '') {
            return null;
        }
        if (preg_match('/\A[A-Za-z0-9\-]{1,64}\z/', $barcodeHint) !== 1) {
            return null;
        }

        return $barcodeHint;
    }

    private function detectMimeType(string $imagePath): string
    {
        $ext = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

        return match ($ext) {
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }
}
