<?php
namespace saso\member;

use saso\repository\Updater;
use saso\framework\View;
use saso\repository\member\Delete;
use saso\common\FailEmptyView;

final class DeleteUsecase
{
    public function __construct(
        private array $post,
        private Updater $updater
    ) {}
    public function exec(): View
    {
        $id = $this->post['id'] ?? '';
        if ($id) {
            $this->updater->exec(new Delete(), ['id' => $id]);
        }
        header('Location: ../start/');
        exit;
    }
}
