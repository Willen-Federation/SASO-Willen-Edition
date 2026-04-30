<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Ai;

use Saso\Domain\Ai\AiAssistant;
use Saso\Domain\Ai\AiUsage;
use Saso\Domain\Ai\ChatMessage;
use Saso\Domain\Ai\ChatRequest;
use Saso\Domain\Ai\ChatResponse;
use Saso\Domain\Ai\ChatRole;
use Saso\Domain\Ai\EmbeddingRequest;
use Saso\Domain\Ai\EmbeddingResponse;
use Saso\Domain\Ai\Exception\AiProviderNotConfiguredException;
use Saso\Domain\Ai\Exception\AiRateLimitedException;
use Saso\Domain\Ai\Exception\AiResponseMalformedException;
use Saso\Domain\Ai\Exception\AiUpstreamException;
use Saso\Domain\Ai\ImageDescriptionResponse;
use Saso\Domain\Ai\ImageRequest;
use Saso\Domain\Ai\StructuredExtractionRequest;
use Saso\Domain\Ai\StructuredExtractionResponse;

final class ClaudeAssistant implements AiAssistant
{
    public const PROVIDER_NAME = 'claude';

    private string $chatModel   = 'claude-3-5-haiku-20241022';
    private string $visionModel = 'claude-3-5-sonnet-20241022';

    private const BASE_URL = 'https://api.anthropic.com/v1/messages';

    public function __construct(private readonly string $apiKey)
    {
    }

    public function chatComplete(ChatRequest $request): ChatResponse
    {
        [$system, $messages] = $this->splitSystemAndMessages($request->messages);

        $body = [
            'model'      => $this->chatModel,
            'max_tokens' => $request->maxTokens,
            'messages'   => $messages,
        ];

        if ($system !== null) {
            $body['system'] = $system;
        }

        $result = $this->post($body);

        $content = $this->extractTextContent($result);
        $usage   = $result['usage'] ?? [];

        return new ChatResponse(
            content: $content,
            usage: new AiUsage(
                promptTokens: (int) ($usage['input_tokens'] ?? 0),
                completionTokens: (int) ($usage['output_tokens'] ?? 0),
            ),
            model: $result['model'] ?? $this->chatModel,
            finishReason: $result['stop_reason'] ?? null,
        );
    }

    public function extractStructured(StructuredExtractionRequest $request): StructuredExtractionResponse
    {
        $userContent = [];

        if ($request->imageBytes !== null) {
            $mime          = $request->imageMimeType ?? 'image/jpeg';
            $userContent[] = [
                'type'   => 'image',
                'source' => [
                    'type'       => 'base64',
                    'media_type' => $mime,
                    'data'       => base64_encode($request->imageBytes),
                ],
            ];
        }

        $userContent[] = ['type' => 'text', 'text' => $request->sourceText !== '' ? $request->sourceText : 'Extract information from the image above.'];

        $body = [
            'model'      => $this->visionModel,
            'max_tokens' => $request->maxTokens,
            'system'     => $request->instruction,
            'messages'   => [
                ['role' => 'user', 'content' => $userContent],
            ],
            'tools' => [
                [
                    'name'         => 'extract_data',
                    'description'  => 'Extract structured data according to the schema',
                    'input_schema' => $request->jsonSchema,
                ],
            ],
            'tool_choice' => ['type' => 'tool', 'name' => 'extract_data'],
        ];

        $result = $this->post($body);

        $toolUseBlock = null;
        foreach ($result['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'tool_use') {
                $toolUseBlock = $block;
                break;
            }
        }

        if ($toolUseBlock === null || !is_array($toolUseBlock['input'] ?? null)) {
            throw AiResponseMalformedException::for(self::PROVIDER_NAME, 'No tool_use block in response');
        }

        $usage = $result['usage'] ?? [];

        return new StructuredExtractionResponse(
            data: $toolUseBlock['input'],
            usage: new AiUsage(
                promptTokens: (int) ($usage['input_tokens'] ?? 0),
                completionTokens: (int) ($usage['output_tokens'] ?? 0),
            ),
            model: $result['model'] ?? $this->visionModel,
        );
    }

    public function embed(EmbeddingRequest $request): EmbeddingResponse
    {
        throw AiProviderNotConfiguredException::for(self::PROVIDER_NAME, 'embed');
    }

    public function describeImage(ImageRequest $request): ImageDescriptionResponse
    {
        $b64  = base64_encode($request->imageBytes);
        $mime = $request->mimeType;

        $content = [
            [
                'type'   => 'image',
                'source' => [
                    'type'       => 'base64',
                    'media_type' => $mime,
                    'data'       => $b64,
                ],
            ],
            ['type' => 'text', 'text' => $request->prompt],
        ];

        $body = [
            'model'      => $this->visionModel,
            'max_tokens' => $request->maxTokens,
            'messages'   => [['role' => 'user', 'content' => $content]],
        ];

        $result      = $this->post($body);
        $text        = $this->extractTextContent($result);
        $usage       = $result['usage'] ?? [];

        return new ImageDescriptionResponse(
            content: $text,
            usage: new AiUsage(
                promptTokens: (int) ($usage['input_tokens'] ?? 0),
                completionTokens: (int) ($usage['output_tokens'] ?? 0),
            ),
            model: $result['model'] ?? $this->visionModel,
            finishReason: $result['stop_reason'] ?? null,
        );
    }

    /**
     * @param list<ChatMessage> $messages
     *
     * @return array{0: string|null, 1: list<array{role: string, content: string}>}
     */
    private function splitSystemAndMessages(array $messages): array
    {
        $system  = null;
        $out     = [];

        foreach ($messages as $msg) {
            if ($msg->role === ChatRole::System) {
                $system = $msg->content;
                continue;
            }
            $out[] = [
                'role'    => $msg->role === ChatRole::Assistant ? 'assistant' : 'user',
                'content' => $msg->content,
            ];
        }

        return [$system, $out];
    }

    /**
     * @param array<string, mixed> $result
     */
    private function extractTextContent(array $result): string
    {
        foreach ($result['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                return (string) ($block['text'] ?? '');
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     */
    private function post(array $body): array
    {
        $ch = curl_init(self::BASE_URL);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: '.$this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
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

        if ($httpCode === 429 || $httpCode === 529) {
            throw AiRateLimitedException::for(self::PROVIDER_NAME);
        }

        if ($httpCode >= 400) {
            $type   = $data['error']['type'] ?? '';
            $reason = $data['error']['message'] ?? "HTTP {$httpCode}";

            if ($type === 'overloaded_error') {
                throw AiUpstreamException::for(self::PROVIDER_NAME, $reason);
            }

            throw AiUpstreamException::for(self::PROVIDER_NAME, $reason);
        }

        if (!is_array($data)) {
            throw AiResponseMalformedException::for(self::PROVIDER_NAME, 'Response is not valid JSON');
        }

        return $data;
    }
}
