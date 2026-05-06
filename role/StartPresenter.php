<?php
namespace saso\role;

use saso\framework\View;

final class StartPresenter
{
    public function __construct(private View $view) {}
    public function view(array $roles): View
    {
        $this->view->roles = $roles;
        return $this->view;
    }
}
