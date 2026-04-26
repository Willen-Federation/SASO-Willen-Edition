<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Controller;

use Saso\Presentation\Api\V1\HttpRequest;
use Saso\Presentation\Api\V1\Response\RawResponse;

/**
 * Renders the Swagger UI bootstrap page for `/api/v1/docs`.
 *
 * Assets are loaded from the pinned unpkg URLs. We accept the
 * third-party fetch in exchange for not vendoring the Swagger UI bundle
 * (~3 MB) into the repository — operators who require an offline
 * deployment can replace this controller with a static-asset variant
 * later, the contract (a 200 HTML response with Swagger pointing at
 * `/api/v1/openapi.yaml`) does not change.
 */
final class SwaggerUiController
{
    private const SWAGGER_UI_VERSION = '5.17.14';

    public function page(HttpRequest $request): RawResponse
    {
        $version = self::SWAGGER_UI_VERSION;
        $html    = <<<HTML
            <!doctype html>
            <html lang="en">
            <head>
              <meta charset="utf-8" />
              <title>SASO REST API — Swagger UI</title>
              <meta name="viewport" content="width=device-width,initial-scale=1" />
              <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@{$version}/swagger-ui.css" />
              <link rel="icon" href="data:," />
            </head>
            <body>
              <div id="swagger-ui"></div>
              <script src="https://unpkg.com/swagger-ui-dist@{$version}/swagger-ui-bundle.js" crossorigin></script>
              <script>
                window.onload = () => {
                  window.ui = SwaggerUIBundle({
                    url: '/api/v1/openapi.yaml',
                    dom_id: '#swagger-ui',
                    deepLinking: true,
                    presets: [SwaggerUIBundle.presets.apis],
                  });
                };
              </script>
            </body>
            </html>
            HTML;

        return new RawResponse(
            status: 200,
            body: $html,
            contentType: 'text/html; charset=utf-8',
        );
    }
}
