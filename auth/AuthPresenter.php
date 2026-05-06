<?php
namespace saso\auth;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class AuthPresenter implements Presenter
{
    public function __construct(
        private View $success,
    )
    {
    }
    public function complete(Either $output): View
    {
        return $output->flatMap(
            $this->success->restoredPath(fn($v)=>$v->restoredPath)
        )->flatMap(
            $this->success->isError(fn($v)=>$v->isError)
        )->flatMap(
            $this->success->providers(fn($v)=>$v->providers)
        )->flatMap(
            fn($v)=>$this->success
        )->getOrElse($this->success);
    }
}
