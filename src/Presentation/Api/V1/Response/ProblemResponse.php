<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Response;

/**
 * Convenience factory for RFC 7807 problem+json responses in the API layer.
 */
final class ProblemResponse
{
    public static function notFound(string $code, string $detail): JsonResponse
    {
        return new JsonResponse(
            status: 404,
            body: [
                'type'   => 'https://docs.willen-federation.org/error-codes#'.$code,
                'title'  => 'Not Found',
                'status' => 404,
                'detail' => $detail,
                'code'   => $code,
            ],
            headers: ['Content-Type' => 'application/problem+json; charset=utf-8'],
        );
    }

    public static function unprocessable(string $code, string $detail): JsonResponse
    {
        return new JsonResponse(
            status: 422,
            body: [
                'type'   => 'https://docs.willen-federation.org/error-codes#'.$code,
                'title'  => 'Unprocessable Entity',
                'status' => 422,
                'detail' => $detail,
                'code'   => $code,
            ],
            headers: ['Content-Type' => 'application/problem+json; charset=utf-8'],
        );
    }
}
