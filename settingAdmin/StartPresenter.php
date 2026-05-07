<?php
namespace saso\settingAdmin;

use saso\framework\Output;
use saso\framework\Presenter;

final class StartPresenter implements Presenter
{
    use Output;

    public function __construct(private StartView $view)
    {
    }

    public function display(): void
    {
        $this->view->display();
    }
}
