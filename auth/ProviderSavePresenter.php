<?php

namespace saso\auth;

use saso\common\RegisterSuccessView;
use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class ProviderSavePresenter implements Presenter
{
    public function __construct(
        private View $errorView,
    ) {
    }

    public function complete(Either $output): View
    {
        /** @var ProviderNewInput $data */
        $data = $output->getOrElse(new ProviderNewInput());

        if ($data->errorMessage !== '') {
            return $output->flatMap(
                $this->errorView->errorMessage(fn ($v) => $v->errorMessage)
            )->flatMap(
                fn ($v) => $this->errorView
            )->getOrElse($this->errorView);
        }

        $successView = new RegisterSuccessView();
        $successView->to(fn () => './auth/providers/?saved=1');
        return $successView;
    }
}
