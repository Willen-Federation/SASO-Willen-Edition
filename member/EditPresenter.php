<?php
namespace saso\member;
use saso\framework\View;
use saso\entity\Member;
final class EditPresenter
{
    public function __construct(private View $view) {}
    public function view(Member $member, string $error, bool $isAdmin = false): View
    {
        $this->view->member = $member;
        $this->view->error = $error;
        $this->view->isAdmin = $isAdmin;
        return $this->view;
    }
}
