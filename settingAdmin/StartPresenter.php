<?php
namespace saso\settingAdmin;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class StartPresenter implements Presenter
{
    public function __construct(private StartView $view)
    {
    }

    public function complete(Either $output): View
    {
        return $this->view;
    }
}
