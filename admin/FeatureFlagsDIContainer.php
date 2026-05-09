<?php
namespace saso\admin;

use saso\framework\DIContainer;
use saso\framework\View;
use saso\Infrastructure\FeatureFlag\PdoFeatureFlagRepository;

final class FeatureFlagsDIContainer implements DIContainer
{
    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        // Flags are loaded lazily from the API endpoint; view renders static HTML with API calls
    }

    public function flow(): View
    {
        return new FeatureFlagsView();
    }
}
