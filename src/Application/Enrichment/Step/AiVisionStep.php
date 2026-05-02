<?php

declare(strict_types=1);

namespace Saso\Application\Enrichment\Step;

use Saso\Domain\Ai\AiAssistant;
use Saso\Domain\Ai\Exception\AiContentPolicyException;
use Saso\Domain\Ai\Exception\AiProviderNotConfiguredException;
use Saso\Domain\Ai\Exception\AiRateLimitedException;
use Saso\Domain\Ai\Exception\AiUpstreamException;
use Saso\Domain\Ai\StructuredExtractionRequest;

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

    public function __construct(private readonly AiAssistant $ai)
    {
    }

    /**
     * @param array<string, mixed> $existing
     *
     * @return array<string, mixed>
     */
    public function run(string $imagePath, ?string $barcodeHint, array $existing): array
    {
        if (!is_readable($imagePath)) {
            return [];
        }

        $imageBytes = file_get_contents($imagePath);
        if ($imageBytes === false || $imageBytes === '') {
            return [];
        }

        $mime        = $this->detectMimeType($imagePath);
        $instruction = $this->buildPrompt($barcodeHint);

        $request = new StructuredExtractionRequest(
            instruction: $instruction,
            sourceText: '',
            jsonSchema: self::EXTRACTION_SCHEMA,
            imageBytes: $imageBytes,
            imageMimeType: $mime,
            maxTokens: 1024,
        );

        try {
            $response = $this->ai->extractStructured($request);

            return $response->data;
        } catch (AiProviderNotConfiguredException | AiRateLimitedException | AiUpstreamException | AiContentPolicyException) {
            return [];
        }
    }

    private function buildPrompt(?string $barcodeHint): string
    {
        $prompt = "この商品画像から製品情報を抽出してください。\n";

        if ($barcodeHint !== null && $barcodeHint !== '') {
            $prompt .= "商品コード: {$barcodeHint}\n";
        }

        $prompt .= "バーコード/QRコードが見えれば jan_code または isbn として記録してください。\n";
        $prompt .= "商品名・メーカー・価格・説明を日本語で記述してください。\n";
        $prompt .= '不明な項目は null にしてください。';

        return $prompt;
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
