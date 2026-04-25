<?php
namespace saso\common;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class EmptyPresenter implements Presenter
{
    public function __construct(
        private View $success,
    )
    {
    }
    public function complete(Either $output): View
    {
        return $this->success;
    }
}
