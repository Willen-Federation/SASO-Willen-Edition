<?php

declare(strict_types=1);

namespace Saso\Domain\Setting;

use InvalidArgumentException;

/**
 * Loads {@see ShelfDimensionConfig} from the system settings.
 *
 * The configuration is stored in `system_setting` under key `shelf.dimension.config`
 * as a JSON value. If the setting does not exist, a default 5-dimension preset is
 * returned for backward compatibility.
 */
final readonly class ShelfDimensionConfigLoader
{
    public function __construct(
        private SystemSettingService $settingService,
    ) {
    }

    public function load(): ShelfDimensionConfig
    {
        try {
            $key = new SettingKey('shelf.dimension.config');
            $setting = $this->settingService->get($key);

            if ($setting === null) {
                return $this->loadDefault();
            }

            return ShelfDimensionConfig::fromJson($setting->asJson());
        } catch (InvalidArgumentException) {
            return $this->loadDefault();
        }
    }

    private function loadDefault(): ShelfDimensionConfig
    {
        $defaultDimensions = [
            new ShelfDimensionMetadata(
                name: '区分コード',
                description: '製品の分類用',
                type: ShelfDimensionType::Letter,
                position: 1,
                enabled: true,
            ),
            new ShelfDimensionMetadata(
                name: 'グループ番号',
                description: '',
                type: ShelfDimensionType::Numeric,
                position: 2,
                enabled: true,
            ),
            new ShelfDimensionMetadata(
                name: '棚番号',
                description: '',
                type: ShelfDimensionType::Numeric,
                position: 3,
                enabled: true,
            ),
            new ShelfDimensionMetadata(
                name: '位置',
                description: '',
                type: ShelfDimensionType::Numeric,
                position: 4,
                enabled: true,
            ),
            new ShelfDimensionMetadata(
                name: '',
                description: '',
                type: ShelfDimensionType::Numeric,
                position: 5,
                enabled: false,
            ),
        ];

        return new ShelfDimensionConfig($defaultDimensions);
    }
}
