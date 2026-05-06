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
            $this->success->authMethods(fn($v) => $v->authMethods)
        )->flatMap(
            $this->success->availableProviders(fn($v) => $v->availableProviders)
        )->flatMap(
            $this->success->passkeys(fn($v) => $v->passkeys)
        )->flatMap(
            fn($v) => $this->success
        )->getOrElse($this->success);
    }
}
