<?php
namespace saso\common;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class RedirectOrErrorPresenter implements Presenter
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
            $this->success->to(fn($v)=>$v)
        )->flatMap(
            fn($v)=>$this->success
        )->orElse(
            fn($v)=>Either::left($this->failure->errorMessage(fn($e)=>$e)($v))
        )->getOrElse($this->failure);
    }
}