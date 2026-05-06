<?php

namespace saso\auth;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class AuthPresenter implements Presenter
{
    public function __construct(
        private AuthView $success,
    ) {
    }
    public function complete(Either $output): View
    {
        $output->flatMap(
            $this->success->restoredPath(fn ($v) => $v->restoredPath)
        )->flatMap(
            $this->success->isError(fn ($v) => $v->isError)
        )->flatMap(
            $this->success->providers(fn ($v) => $v->providers)
        );

        return $this->success;
    }
}
