<?php
namespace saso\settingAdmin;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class ShelfSettingsPresenter implements Presenter
{
    public function __construct(
        private View $success,
        private View $failure,
    ) {
    }

    public function complete(Either $output): View
    {
        return $output
            ->map(fn($msg) => $this->success->message(fn() => $msg))
            ->orElse(fn($err) => Either::left($this->failure->errorMessage(fn() => $err)($err)))
            ->getOrElse($this->failure);
    }
}
