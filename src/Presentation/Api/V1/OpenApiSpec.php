<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;

/**
 * In-memory representation of `config/openapi.yaml`.
 *
 * The spec is parsed once at boot and exposed in two shapes:
 *
 *   * {@see routes()} — the dispatch table the {@see Router} feeds into
 *     fast-route. Every route declared in the YAML must carry an
 *     `operationId`; the loader rejects anything else.
 *   * {@see rawYaml()} — the original YAML text, served verbatim by
 *     `GET /api/v1/openapi.yaml` so SDK generators do not have to
 *     re-marshal a parsed-and-rebuilt copy.
 */
final class OpenApiSpec
{
    /**
     * @param list<Route> $routes
     * @param array<string, mixed> $document parsed YAML
     */
    private function __construct(
        private readonly array $routes,
        private readonly array $document,
        private readonly string $rawYaml,
    ) {
    }

    public static function load(string $yamlPath): self
    {
        if (!is_file($yamlPath)) {
            throw new RuntimeException(sprintf(
                'OpenAPI specification not found: %s',
                $yamlPath,
            ));
        }

        $contents = (string) file_get_contents($yamlPath);

        return self::fromYamlString($contents);
    }

    public static function fromYamlString(string $yaml): self
    {
        $document = Yaml::parse($yaml);
        if (!is_array($document)) {
            throw new RuntimeException('OpenAPI document must be a YAML map.');
        }

        return self::fromArray($document, $yaml);
    }

    /**
     * @param array<string, mixed> $document
     */
    public static function fromArray(array $document, string $rawYaml = ''): self
    {
        $routes = self::extractRoutes($document);

        return new self($routes, $document, $rawYaml);
    }

    /**
     * @return list<Route>
     */
    public function routes(): array
    {
        return $this->routes;
    }

    /**
     * @return array<string, mixed>
     */
    public function document(): array
    {
        return $this->document;
    }

    public function rawYaml(): string
    {
        return $this->rawYaml;
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return list<Route>
     */
    private static function extractRoutes(array $document): array
    {
        $paths = $document['paths'] ?? null;
        if (!is_array($paths)) {
            return [];
        }

        $allowedMethods = ['get', 'post', 'put', 'patch', 'delete', 'head', 'options'];
        $routes         = [];

        foreach ($paths as $path => $pathItem) {
            if (!is_string($path) || !is_array($pathItem)) {
                continue;
            }

            foreach ($pathItem as $method => $operation) {
                if (!is_string($method) || !in_array(strtolower($method), $allowedMethods, true)) {
                    continue;
                }
                if (!is_array($operation)) {
                    continue;
                }

                $operationId = $operation['operationId'] ?? null;
                if (!is_string($operationId) || $operationId === '') {
                    throw new RuntimeException(sprintf(
                        'OpenAPI operation %s %s must declare an operationId.',
                        strtoupper($method),
                        $path,
                    ));
                }

                $routes[] = new Route(strtoupper($method), $path, $operationId);
            }
        }

        return $routes;
    }
}
