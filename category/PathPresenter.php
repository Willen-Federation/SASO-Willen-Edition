<?php
namespace saso\category;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class PathPresenter implements Presenter
{
    public function __construct(
        private View $success,
    )
    {
    }
    public function complete(Either $output): View
    {
        return $output->flatMap(
            $this->success->path(fn($v)=>$v)
        )->flatMap(
            fn($v)=>$this->success
        )->getOrElse($this->success);
    }
}