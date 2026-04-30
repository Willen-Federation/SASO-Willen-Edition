<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Ai;

use Saso\Domain\Ai\AiAssistant;
use Saso\Domain\Ai\AiUsage;
use Saso\Domain\Ai\ChatRequest;
use Saso\Domain\Ai\ChatResponse;
use Saso\Domain\Ai\ChatRole;
use Saso\Domain\Ai\EmbeddingRequest;
use Saso\Domain\Ai\EmbeddingResponse;
use Saso\Domain\Ai\Exception\AiContentPolicyException;
use Saso\Domain\Ai\Exception\AiRateLimitedException;
use Saso\Domain\Ai\Exception\AiResponseMalformedException;
use Saso\Domain\Ai\Exception\AiUpstreamException;
use Saso\Domain\Ai\ImageDescriptionResponse;
use Saso\Domain\Ai\ImageRequest;
use Saso\Domain\Ai\StructuredExtractionRequest;
use Saso\Domain\Ai\StructuredExtractionResponse;

final class GeminiAssistant implements AiAssistant
{
    public const PROVIDER_NAME = 'gemini';

    private string $chatModel   = 'gemini-1.5-flash';
    private string $visionModel = 'gemini-1.5-flash';
    private string $embedModel  = 'text-embedding-004';

    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct(private readonly string $apiKey)
    {
    }

    public function chatComplete(ChatRequest $request): ChatResponse
    {
        $systemInstruction = null;
        $contents          = [];

        foreach ($request->messages as $msg) {
            if ($msg->role === ChatRole::System) {
                $systemInstruction = $msg->content;
                continue;
            }
            $contents[] = [
                'role'  => $msg->role === ChatRole::Assistant ? 'model' : 'user',
                'parts' => [['text' => $msg->content]],
            ];
        }

        $generationConfig = [
            'temperature'     => $request->temperature,
            'maxOutputTokens' => $request->maxTokens,
        ];

        if ($request->responseFormat === ChatRequest::FORMAT_JSON_OBJECT) {
            $generationConfig['responseMimeType'] = 'application/json';
        } elseif ($request->responseFormat === ChatRequest::FORMAT_JSON_SCHEMA && $request->jsonSchema !== null) {
            $generationConfig['responseMimeType'] = 'application/json';
            $generationConfig['responseSchema']   = $request->jsonSchema;
        }

        $body = ['contents' => $contents, 'generationConfig' => $generationConfig];
        if ($systemInstruction !== null) {
            $body['systemInstruction'] = ['parts' => [['text' => $systemInstruction]]];
        }

        $result = $this->post("{$this->chatModel}:generateContent", $body);

        $text  = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $usage = $result['usageMetadata'] ?? [];

        return new ChatResponse(
            content: $text,
            usage: new AiUsage(
                promptTokens: (int) ($usage['promptTokenCount'] ?? 0),
                completionTokens: (int) ($usage['candidatesTokenCount'] ?? 0),
            ),
            model: $this->chatModel,
            finishReason: $result['candidates'][0]['finishReason'] ?? null,
        );
    }

    public function extractStructured(StructuredExtractionRequest $request): StructuredExtractionResponse
    {
        $parts = [['text' => $request->instruction."\n\n".$request->sourceText]];

        if ($request->imageBytes !== null) {
            $mime   = $request->imageMimeType ?? 'image/jpeg';
            $parts[] = [
                'inlineData' => [
                    'mimeType' => $mime,
                    'data'     => base64_encode($request->imageBytes),
                ],
            ];
        }

        $generationConfig = [
            'temperature'      => 0.0,
            'maxOutputTokens'  => $request->maxTokens,
            'responseMimeType' => 'application/json',
            'responseSchema'   => $request->jsonSchema,
        ];

        $body   = [
            'contents'         => [['role' => 'user', 'parts' => $parts]],
            'generationConfig' => $generationConfig,
        ];

        $result  = $this->post("{$this->visionModel}:generateContent", $body);
        $text    = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $decoded = json_decode($text, associative: true);

        if (!is_array($decoded)) {
            throw AiResponseMalformedException::for(self::PROVIDER_NAME, 'Response is not a valid JSON object');
        }

        $usage = $result['usageMetadata'] ?? [];

        return new StructuredExtractionResponse(
            data: $decoded,
            usage: new AiUsage(
                promptTokens: (int) ($usage['promptTokenCount'] ?? 0),
                completionTokens: (int) ($usage['candidatesTokenCount'] ?? 0),
            ),
            model: $this->visionModel,
        );
    }

    public function embed(EmbeddingRequest $request): EmbeddingResponse
    {
        $requests = [];
        foreach ($request->textInputs as $text) {
            $requests[] = ['content' => ['parts' => [['text' => $text]]]];
        }

        $body   = ['requests' => $requests];
        $result = $this->post("{$this->embedModel}:batchEmbedContents", $body);

        $vectors = [];
        foreach ($result['embeddings'] ?? [] as $emb) {
            $vectors[] = $emb['values'] ?? [];
        }

        if ($vectors === []) {
            throw AiResponseMalformedException::for(self::PROVIDER_NAME, 'No embedding vectors returned');
        }

        return new EmbeddingResponse(
            vectors: $vectors,
            usage: new AiUsage(),
            model: $this->embedModel,
        );
    }

    public function describeImage(ImageRequest $request): ImageDescriptionResponse
    {
        $b64   = base64_encode($request->imageBytes);
        $mime  = $request->mimeType;

        $parts = [
            [
                'inlineData' => [
                    'mimeType' => $mime,
                    'data'     => $b64,
                ],
            ],
            ['text' => $request->prompt],
        ];

        $generationConfig = [
            'temperature'     => 0.0,
            'maxOutputTokens' => $request->maxTokens,
        ];

        if ($request->responseFormat === ChatRequest::FORMAT_JSON_OBJECT) {
            $generationConfig['responseMimeType'] = 'application/json';
        }

        $body   = [
            'contents'         => [['role' => 'user', 'parts' => $parts]],
            'generationConfig' => $generationConfig,
        ];

        $result = $this->post("{$this->visionModel}:generateContent", $body);

        $text  = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $usage = $result['usageMetadata'] ?? [];

        return new ImageDescriptionResponse(
            content: $text,
            usage: new AiUsage(
                promptTokens: (int) ($usage['promptTokenCount'] ?? 0),
                completionTokens: (int) ($usage['candidatesTokenCount'] ?? 0),
            ),
            model: $this->visionModel,
            finishReason: $result['candidates'][0]['finishReason'] ?? null,
        );
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     */
    private function post(string $endpoint, array $body): array
    {
        $url = self::BASE_URL.'/'.$endpoint.'?key='.urlencode($this->apiKey);
        $ch  = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $raw      = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw AiUpstreamException::for(self::PROVIDER_NAME, $curlErr ?: 'curl error');
        }

        $data = json_decode((string) $raw, associative: true);

        if ($httpCode === 429) {
            throw AiRateLimitedException::for(self::PROVIDER_NAME);
        }

        if ($httpCode === 400) {
            $reason = $data['error']['message'] ?? 'bad request';
            $status = $data['error']['status'] ?? '';
            if (str_contains(strtolower($status), 'safety') || str_contains(strtolower($reason), 'safety')) {
                throw AiContentPolicyException::for(self::PROVIDER_NAME, $reason);
            }
            throw AiUpstreamException::for(self::PROVIDER_NAME, $reason);
        }

        if ($httpCode >= 400) {
            $reason = $data['error']['message'] ?? "HTTP {$httpCode}";
            throw AiUpstreamException::for(self::PROVIDER_NAME, $reason);
        }

        if (!is_array($data)) {
            throw AiResponseMalformedException::for(self::PROVIDER_NAME, 'Response is not valid JSON');
        }

        $blockReason = $data['candidates'][0]['finishReason'] ?? null;
        if ($blockReason !== null && str_starts_with($blockReason, 'SAFETY')) {
            throw AiContentPolicyException::for(self::PROVIDER_NAME, $blockReason);
        }

        return $data;
    }
}
