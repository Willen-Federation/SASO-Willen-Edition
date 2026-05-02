<?php

declare(strict_types=1);

namespace Saso\Infrastructure\Ai;

use OpenAI\Client as OpenAiClient;
use OpenAI\Exceptions\ErrorException;
use OpenAI\Exceptions\TransporterException;
use Saso\Domain\Ai\AiAssistant;
use Saso\Domain\Ai\AiUsage;
use Saso\Domain\Ai\ChatMessage;
use Saso\Domain\Ai\ChatRequest;
use Saso\Domain\Ai\ChatResponse;
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

final class OpenAiAssistant implements AiAssistant
{
    public const PROVIDER_NAME = 'openai';

    private string $chatModel   = 'gpt-4o-mini';
    private string $visionModel = 'gpt-4o';
    private string $embedModel  = 'text-embedding-3-small';

    public function __construct(private readonly OpenAiClient $client)
    {
    }

    public function chatComplete(ChatRequest $request): ChatResponse
    {
        $messages = $this->buildMessages($request->messages);

        $params = [
            'model'                  => $this->chatModel,
            'messages'               => $messages,
            'temperature'            => $request->temperature,
            'max_completion_tokens'  => $request->maxTokens,
        ];

        if ($request->responseFormat === ChatRequest::FORMAT_JSON_OBJECT) {
            $params['response_format'] = ['type' => 'json_object'];
        } elseif ($request->responseFormat === ChatRequest::FORMAT_JSON_SCHEMA && $request->jsonSchema !== null) {
            $params['response_format'] = [
                'type'        => 'json_schema',
                'json_schema' => [
                    'name'   => 'response',
                    'strict' => true,
                    'schema' => $request->jsonSchema,
                ],
            ];
        }

        try {
            $response = $this->client->chat()->create($params);
        } catch (ErrorException $e) {
            throw $this->mapErrorException($e);
        } catch (TransporterException $e) {
            throw AiUpstreamException::for(self::PROVIDER_NAME, $e->getMessage(), $e);
        }

        $choice  = $response->choices[0] ?? null;
        $content = $choice?->message->content ?? '';
        $usage   = $response->usage;

        return new ChatResponse(
            content: $content,
            usage: new AiUsage(
                promptTokens: $usage?->promptTokens ?? 0,
                completionTokens: $usage?->completionTokens ?? 0,
            ),
            model: $response->model,
            finishReason: $choice?->finishReason,
        );
    }

    public function extractStructured(StructuredExtractionRequest $request): StructuredExtractionResponse
    {
        $userParts = [];
        if ($request->sourceText !== '') {
            $userParts[] = ['type' => 'text', 'text' => $request->sourceText];
        }
        if ($request->imageBytes !== null) {
            $mime      = $request->imageMimeType ?? 'image/jpeg';
            $b64       = base64_encode($request->imageBytes);
            $userParts[] = [
                'type'      => 'image_url',
                'image_url' => ['url' => "data:{$mime};base64,{$b64}"],
            ];
        }

        $messages = [
            ['role' => 'system', 'content' => $request->instruction],
            ['role' => 'user', 'content' => $userParts],
        ];

        $params = [
            'model'                 => $this->visionModel,
            'messages'              => $messages,
            'temperature'           => 0.0,
            'max_completion_tokens' => $request->maxTokens,
            'response_format'       => [
                'type'        => 'json_schema',
                'json_schema' => [
                    'name'   => 'extraction',
                    'strict' => true,
                    'schema' => $request->jsonSchema,
                ],
            ],
        ];

        try {
            $response = $this->client->chat()->create($params);
        } catch (ErrorException $e) {
            throw $this->mapErrorException($e);
        } catch (TransporterException $e) {
            throw AiUpstreamException::for(self::PROVIDER_NAME, $e->getMessage(), $e);
        }

        $content = $response->choices[0]?->message->content ?? '';
        $decoded = json_decode($content, associative: true);

        if (!is_array($decoded)) {
            throw AiResponseMalformedException::for(self::PROVIDER_NAME, 'Response is not a valid JSON object');
        }

        $usage = $response->usage;

        return new StructuredExtractionResponse(
            data: $decoded,
            usage: new AiUsage(
                promptTokens: $usage?->promptTokens ?? 0,
                completionTokens: $usage?->completionTokens ?? 0,
            ),
            model: $response->model,
        );
    }

    public function embed(EmbeddingRequest $request): EmbeddingResponse
    {
        $inputs = $request->textInputs;

        $params = [
            'model' => $this->embedModel,
            'input' => $inputs,
        ];

        if ($request->dimensions !== null) {
            $params['dimensions'] = $request->dimensions;
        }

        try {
            $response = $this->client->embeddings()->create($params);
        } catch (ErrorException $e) {
            throw $this->mapErrorException($e);
        } catch (TransporterException $e) {
            throw AiUpstreamException::for(self::PROVIDER_NAME, $e->getMessage(), $e);
        }

        $vectors = [];
        foreach ($response->embeddings as $embedding) {
            $vectors[] = $embedding->embedding;
        }

        $usage = $response->usage;

        return new EmbeddingResponse(
            vectors: $vectors,
            usage: new AiUsage(
                embeddingTokens: $usage?->promptTokens ?? 0,
            ),
            model: $response->model ?? $this->embedModel,
        );
    }

    public function describeImage(ImageRequest $request): ImageDescriptionResponse
    {
        $b64  = base64_encode($request->imageBytes);
        $mime = $request->mimeType;

        $messages = [
            [
                'role'    => 'user',
                'content' => [
                    [
                        'type'      => 'image_url',
                        'image_url' => ['url' => "data:{$mime};base64,{$b64}"],
                    ],
                    [
                        'type' => 'text',
                        'text' => $request->prompt,
                    ],
                ],
            ],
        ];

        $params = [
            'model'                 => $this->visionModel,
            'messages'              => $messages,
            'temperature'           => 0.0,
            'max_completion_tokens' => $request->maxTokens,
        ];

        if ($request->responseFormat === ChatRequest::FORMAT_JSON_OBJECT) {
            $params['response_format'] = ['type' => 'json_object'];
        }

        try {
            $response = $this->client->chat()->create($params);
        } catch (ErrorException $e) {
            throw $this->mapErrorException($e);
        } catch (TransporterException $e) {
            throw AiUpstreamException::for(self::PROVIDER_NAME, $e->getMessage(), $e);
        }

        $choice  = $response->choices[0] ?? null;
        $content = $choice?->message->content ?? '';
        $usage   = $response->usage;

        return new ImageDescriptionResponse(
            content: $content,
            usage: new AiUsage(
                promptTokens: $usage?->promptTokens ?? 0,
                completionTokens: $usage?->completionTokens ?? 0,
            ),
            model: $response->model,
            finishReason: $choice?->finishReason,
        );
    }

    /**
     * @param list<ChatMessage> $messages
     *
     * @return list<array{role: string, content: string}>
     */
    private function buildMessages(array $messages): array
    {
        return array_map(static fn (ChatMessage $m) => [
            'role'    => $m->role->value,
            'content' => $m->content,
        ], $messages);
    }

    private function mapErrorException(ErrorException $e): AiRateLimitedException|AiContentPolicyException|AiUpstreamException
    {
        if ($e->getStatusCode() === 429) {
            return AiRateLimitedException::for(self::PROVIDER_NAME);
        }

        if ($e->getErrorType() === 'content_filter') {
            return AiContentPolicyException::for(self::PROVIDER_NAME, $e->getErrorMessage());
        }

        return AiUpstreamException::for(self::PROVIDER_NAME, $e->getErrorMessage(), $e);
    }
}
