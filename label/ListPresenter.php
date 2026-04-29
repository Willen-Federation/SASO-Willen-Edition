<?php
namespace saso\label;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class ListPresenter implements Presenter
{
    public function __construct(
        private View $success,
    )
    {
    }
    public function complete(Either $output): View
    {
        $output->flatMap(
            $this->success->labels(fn($v) => $v)
        );

        return $this->success;
    }
}