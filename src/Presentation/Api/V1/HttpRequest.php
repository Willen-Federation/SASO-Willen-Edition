<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1;

/**
 * Minimal request value object.
 *
 * Intentionally not PSR-7 — that pulls in an interface contract this app
 * does not need yet. When (if) we adopt PSR-7 wholesale, this class
 * becomes a simple adapter over `Psr\Http\Message\ServerRequestInterface`
 * without leaking that change into controllers.
 */
final readonly class HttpRequest
{
    /**
     * @param array<string, string|list<string>> $headers raw header bag
     * @param array<string, string> $query parsed query string
     * @param array<string, string> $pathParams URI parameters captured by the router
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $headers = [],
        public array $query = [],
        public array $pathParams = [],
        public ?string $body = null,
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri    = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path   = (string) (parse_url($uri, PHP_URL_PATH) ?? '/');

        $query = [];
        parse_str((string) (parse_url($uri, PHP_URL_QUERY) ?? ''), $query);
        /** @var array<string, string> $query */

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (is_string($key) && str_starts_with($key, 'HTTP_') && is_string($value)) {
                $name           = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }

        return new self(
            method: $method,
            path: $path,
            headers: $headers,
            query: $query,
            body: file_get_contents('php://input') ?: null,
        );
    }

    public function header(string $name): ?string
    {
        $value = $this->headers[strtolower($name)] ?? null;

        if (is_array($value)) {
            return $value[0] ?? null;
        }

        return $value;
    }
}
