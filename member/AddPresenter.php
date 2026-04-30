<?php
namespace saso\member;
use saso\framework\View;
final class AddPresenter
{
    public function __construct(private View $view) {}
    public function view(string $error): View
    {
        $this->view->error = $error;
        return $this->view;
    }
}
