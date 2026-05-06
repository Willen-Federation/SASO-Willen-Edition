<?php
namespace saso\installer;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class InstallPresenter implements Presenter
{
    public function __construct(
        private View $success,
        private View $failure,
    )
    {
    }
    public function complete(Either $output): View
    {
        $result = $output->flatMap(
            $this->success->member(fn($v) => $v)
        );

        return $result->isRight() ? $this->success : $this->failure;
    }
}