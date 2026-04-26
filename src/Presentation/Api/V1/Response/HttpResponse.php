<?php

declare(strict_types=1);

namespace Saso\Presentation\Api\V1\Response;

/**
 * Marker interface for everything a controller can return.
 *
 * Concrete implementations: {@see JsonResponse}, {@see RawResponse}.
 * The router calls {@see emit()} on whichever it receives.
 */
interface HttpResponse
{
    public function emit(): void;
}
