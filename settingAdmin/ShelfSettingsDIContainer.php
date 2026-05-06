<?php
namespace saso\settingAdmin;

use saso\framework\DIContainer;
use saso\framework\View;

final class ShelfSettingsDIContainer implements DIContainer
{
    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
    }

    public function flow(): View
    {
        return new ShelfSettingsView($_POST);
    }
}
