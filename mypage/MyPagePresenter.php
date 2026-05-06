<?php
namespace saso\mypage;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class MyPagePresenter implements Presenter
{
    public function __construct(
        private View $success,
    )
    {
    }

    public function complete(Either $output): View
    {
        return $output->flatMap(
            $this->success->member(fn($v) => $v->member)
        )->flatMap(
            fn($v) => $this->success
        )->getOrElse($this->success);
    }
}
