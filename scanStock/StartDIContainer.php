<?php

declare(strict_types=1);

namespace saso\scanStock;

use saso\framework\DIContainer;
use saso\framework\View;

/**
 * DI container for GET /scan/stock/
 *
 * Sets up the Scan-to-Stock start view.
 */
final class StartDIContainer implements DIContainer
{
    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        // No heavy dependencies needed for the initial page load.
        // Barcode lookup and stock registration are handled client-side via
        // the existing API endpoints (/api/v1/barcode/{code}) and form POSTs.
    }

    public function flow(): View
    {
        return new StartView();
    }
}
