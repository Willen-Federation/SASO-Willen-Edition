<?php
namespace saso\shelf;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class ShelvesPresenter implements Presenter
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
            $this->success->shelves(fn($v) => $v)
        );

        return $result->isRight() ? $this->success : $this->failure;
    }
}