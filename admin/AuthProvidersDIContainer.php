<?php
namespace saso\admin;

use saso\framework\DIContainer;
use saso\framework\View;

final class AuthProvidersDIContainer implements DIContainer
{
    public function isTopLevel(): bool
    {
        return true;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        // Auth providers loaded via API; placeholders for initial render
    }

    public function flow(): View
    {
        return new AuthProvidersView();
    }
}
