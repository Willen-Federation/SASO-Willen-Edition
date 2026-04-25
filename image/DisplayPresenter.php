<?php
namespace saso\image;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class DisplayPresenter implements Presenter
{
    public function __construct(
        private View $success,
        private View $failure,
    )
    {
    }
    public function complete(Either $output): View
    {
        return $output->flatMap(
            $this->success->image(fn($v)=>$v)
        )->flatMap(
            fn($v)=>$this->success
        )->getOrElse($this->failure);
    }
}