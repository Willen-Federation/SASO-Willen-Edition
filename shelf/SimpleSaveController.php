<?php
namespace saso\shelf;

use saso\framework\View;
use saso\common\RedirectView;

final class SimpleSaveController
{
    public function __construct(
        private SimpleSaveUsecase $usecase
    ) {
    }

    public function handle(array $post): View
    {
        $dataJson = $post['data'] ?? null;
        if (!$dataJson) {
            return new RedirectView('./shelf/simple/');
        }

        $data = json_decode($dataJson, true);
        if (!$data) {
            return new RedirectView('./shelf/simple/');
        }

        $this->usecase->execute($data);

        return new RedirectView('./shelf/map/');
    }
}
