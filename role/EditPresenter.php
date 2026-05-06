<?php
namespace saso\role;

use saso\entity\Role;
use saso\framework\View;

final class EditPresenter
{
    public function __construct(private View $view) {}
    public function view(Role $role, string $error): View
    {
        $this->view->role  = $role;
        $this->view->error = $error;
        return $this->view;
    }
}
