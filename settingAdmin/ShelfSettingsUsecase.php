<?php
namespace saso\settingAdmin;

use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\util\monad\Either;
use Saso\Domain\Setting\SettingKey;
use Saso\Domain\Setting\SettingValue;
use Saso\Domain\Setting\ShelfDimensionConfig;
use Saso\Domain\Setting\ShelfDimensionMetadata;
use Saso\Infrastructure\Setting\PdoSystemSettingService;

final class ShelfSettingsUsecase implements Usecase
{
    use OutputForSingleEntity;
    private Either $output;

    public function __construct(
        private Presenter $presenter,
        private PdoSystemSettingService $settingService,
        private string $memberId,
    ) {
    }

    public function handle(DTO $data): void
    {
        try {
            $dimensions = [];

            for ($i = 1; $i <= 10; $i++) {
                $enabled = $data->{"dimension{$i}Enabled"} ?? false;
                $dimensions[] = new ShelfDimensionMetadata(
                    name: $data->{"dimension{$i}Name"} ?? '',
                    description: $data->{"dimension{$i}Description"} ?? '',
                    type: $data->{"dimension{$i}Type"} ?? 'numeric',
                    position: $i,
                    enabled: $enabled,
                );
            }

            $config = new ShelfDimensionConfig($dimensions);
            $key = new SettingKey('shelf.dimension.config');
            $value = SettingValue::json($config->toJson());

            $this->settingService->set(
                $key,
                $value,
                $this->memberId,
                'Shelf dimension configuration updated via admin UI'
            );

            $this->output = Either::right('Shelf settings saved successfully.');
        } catch (\Exception $e) {
            $this->output = Either::left('Failed to save shelf settings: ' . $e->getMessage());
        }
    }
}
