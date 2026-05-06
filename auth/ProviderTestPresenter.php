<?php

namespace saso\auth;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class ProviderTestPresenter implements Presenter
{
    public function __construct(
        private View $view,
    ) {
    }

    public function complete(Either $output): View
    {
        return $output->flatMap(
            $this->view->ok(fn($v) => $v->ok)
        )->flatMap(
            $this->view->message(fn($v) => $v->message)
        )->flatMap(
            $this->view->details(fn($v) => $v->details)
        )->flatMap(
            fn($v) => Either::of($this->view)
        )->getOrElse($this->view);
    }
}
