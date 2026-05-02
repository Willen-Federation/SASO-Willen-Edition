<?php
namespace saso\member;

use saso\framework\View;

final class StartPresenter
{
    public function __construct(private View $view) {}
    public function view(array $members): View
    {
        $this->view->members = $members;
        return $this->view;
    }
}
