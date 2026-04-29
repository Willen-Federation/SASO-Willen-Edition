<?php
namespace saso\shelf;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class SinglePresenter implements Presenter
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
            $this->success->shelves(fn($v) => [$v])
        )->flatMap(
            $this->success->pagesAmount(fn($v) => 1)
        )->flatMap(
            $this->success->page(fn($v) => 1)
        )->flatMap(
            $this->success->mins(fn($v) => [])
        )->flatMap(
            $this->success->maxs(fn($v) => [])
        );

        return $result->isRight() ? $this->success : $this->failure;
    }
}