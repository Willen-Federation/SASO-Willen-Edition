<?php
namespace saso\framework;

use saso\common;

trait OnlyPostFlow
{
    private Controller $ctrl;
    private Usecase $usecase;
    private View $view;
    private bool $notPost;
    public function flow(): View
    {
        if($this->notPost) {
            $this->ctrl = new common\EmptyController();
            $this->usecase = new common\EmptyUsecase(
                new common\EmptyPresenter(
                    $this->view
                )
            );
        }
        $this->ctrl->input($this->usecase);
        return $this->usecase->output();
    }
}
