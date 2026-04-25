<?php
namespace saso\auth;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class PasswordPresenter implements Presenter
{
    public function __construct(
        private View $success,
        private View $failure,
    )
    {
    }
    public function complete(Either $output): View
    {
        return $output->flatMap(
            $this->success->to(fn($v)=>'start/password/'.$v.'/1')
        )->flatMap(
            fn($v)=>$this->success
        )->orElse(
            fn($v)=>Either::left($this->failure->errorMessage(fn($e)=>$e)($v))
        )->orElse(
            fn($v)=>Either::left($this->failure->start(fn($e)=>'start/password')($v))
        )->getOrElse($this->failure);
    }
}