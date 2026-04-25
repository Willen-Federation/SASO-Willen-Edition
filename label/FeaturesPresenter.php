<?php
namespace saso\label;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class FeaturesPresenter implements Presenter
{
    public function __construct(
        private View $success,
    )
    {
    }
    public function complete(Either $output): View
    {
        return $output->flatMap(
            $this->success->labelCaches(fn($v)=>$v)
        )->flatMap(
            fn($v)=>$this->success
        )->getOrElse($this->success);
    }
}