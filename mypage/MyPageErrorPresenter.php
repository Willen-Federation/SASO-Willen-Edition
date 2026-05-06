<?php
namespace saso\mypage;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class MyPageErrorPresenter implements Presenter
{
    public function __construct(
        private View $error,
    )
    {
    }

    public function complete(Either $output): View
    {
        return $output->flatMap(
            $this->error->message(fn($v) => $v->error)
        )->flatMap(
            fn($v) => $this->error
        )->getOrElse($this->error);
    }
}
