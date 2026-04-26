<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Response;

/**
 * Verbatim body response — used by `/api/v1/openapi.yaml` (raw YAML) and
 * `/api/v1/docs` (Swagger UI HTML). The caller controls Content-Type.
 */
final readonly class RawResponse implements HttpResponse
{
    /**
     * @param array<string, string> $headers extra headers
     */
    public function __construct(
        public int $status,
        public string $body,
        public string $contentType,
        public array $headers = [],
    ) {
    }

    public function emit(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            header(sprintf('Content-Type: %s', $this->contentType));
            foreach ($this->headers as $name => $value) {
                if (strcasecmp($name, 'Content-Type') !== 0) {
                    header(sprintf('%s: %s', $name, $value));
                }
            }
        }
        echo $this->body;
    }
}
