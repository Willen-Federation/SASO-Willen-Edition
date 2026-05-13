<?php
namespace saso\settingAdmin;

use saso\framework\Controller;
use saso\framework\DTO;
use saso\framework\Getter;

final class ShelfSettingsController implements Controller, DTO
{
    use Getter;

    public function __construct(
        private array $post,
    ) {
    }

    public function hasChanges(): bool
    {
        return count($this->post) > 0 && isset($this->post['save_shelf_config']);
    }

    public function getDimensions(): array
    {
        $dimensions = [];
        for ($i = 1; $i <= 10; $i++) {
            if (isset($this->post["dimension_{$i}_enabled"])) {
                $dimensions[] = [
                    'position' => $i,
                    'name' => $this->post["dimension_{$i}_name"] ?? '',
                    'description' => $this->post["dimension_{$i}_description"] ?? '',
                    'type' => $this->post["dimension_{$i}_type"] ?? 'numeric',
                    'enabled' => true,
                ];
            }
        }
        return $dimensions;
    }
}
