<?php
namespace saso\role;

use saso\framework\View;
use saso\repository\Updater;
use saso\repository\role\Delete;
use saso\common\FailEmptyView;

final class DeleteUsecase
{
    public function __construct(
        private array $post,
        private Updater $updater,
    ) {}

    public function exec(): View
    {
        $name = $this->post['name'] ?? '';
        // Protect built-in roles from deletion
        if ($name !== '' && !in_array($name, ['admin', 'operator'], true)) {
            $this->updater->exec(new Delete(), ['name' => $name]);
        }
        header('Location: ../start/');
        exit;
    }
}
