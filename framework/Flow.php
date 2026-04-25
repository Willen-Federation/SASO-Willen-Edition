<?php
namespace saso\framework;

trait Flow
{
    private Controller $ctrl;
    private Usecase $usecase;
    public function flow(): View
    {
        $this->ctrl->input($this->usecase);
        return $this->usecase->output();
    }
}
