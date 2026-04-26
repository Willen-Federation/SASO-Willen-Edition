<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller;

use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\OpenApiSpec;
use Saso\Presentation\Api\V1\Response\RawResponse;

/**
 * Serves the OpenAPI specification verbatim from `config/openapi.yaml`.
 *
 * Returning the raw YAML (rather than re-emitting a parsed-and-rebuilt
 * copy) keeps the wire output byte-identical to the file under review,
 * which in turn keeps SDK-generation diffs reviewable.
 */
final class OpenApiController
{
    public function __construct(
        private readonly OpenApiSpec $spec,
    ) {
    }

    public function yaml(HttpRequest $request): RawResponse
    {
        return new RawResponse(
            status: 200,
            body: $this->spec->rawYaml(),
            contentType: 'application/yaml; charset=utf-8',
            headers: [
                'Cache-Control' => 'no-cache',
            ],
        );
    }
}
