<?php

namespace saso\auth;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class ProviderNewPresenter implements Presenter
{
    public function __construct(
        private View $view,
    ) {
    }

    public function complete(Either $output): View
    {
        return $output->flatMap(
            $this->view->errorMessage(fn ($v) => $v->errorMessage)
        )->flatMap(
            fn ($v) => $this->view
        )->getOrElse($this->view);
    }
}
