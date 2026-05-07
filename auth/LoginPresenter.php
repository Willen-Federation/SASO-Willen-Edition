<?php
namespace saso\auth;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class LoginPresenter implements Presenter
{
    public function __construct(
        private View $success,
    )
    {
    }
    public function complete(Either $output): View
    {
        $result = $output->flatMap(
            $this->success->restoredPath(fn($v)=>$v)
        )->OrElse(
            fn($v)=>Either::left($this->success->restoredPath(fn($a)=>$a)($v))
        );
        return $this->success;
    }
}