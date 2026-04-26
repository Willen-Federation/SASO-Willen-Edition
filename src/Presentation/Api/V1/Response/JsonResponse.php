<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Response;

/**
 * `application/json` response with Unicode-safe encoding flags.
 */
final readonly class JsonResponse implements HttpResponse
{
    /**
     * @param array<string, mixed>|list<mixed> $body structured payload
     * @param array<string, string> $headers extra headers
     */
    public function __construct(
        public int $status,
        public array $body,
        public array $headers = [],
    ) {
    }

    public function encode(): string
    {
        return (string) json_encode(
            $this->body,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    public function emit(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            $contentTypeSet = false;
            foreach ($this->headers as $name => $value) {
                header(sprintf('%s: %s', $name, $value));
                if (strcasecmp($name, 'Content-Type') === 0) {
                    $contentTypeSet = true;
                }
            }
            if (!$contentTypeSet) {
                header('Content-Type: application/json; charset=utf-8');
            }
        }
        echo $this->encode();
    }
}
