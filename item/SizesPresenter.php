<?php
namespace saso\item;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\Each;
use saso\util\monad\Either;

final class SizesPresenter implements Presenter
{
    public function __construct(
        private View $success,
        private View $failure,
    )
    {
    }
    public function complete(Either $output): View
    {
        return $output->filter(
            fn($v)=>$v->item->getOrElse(false)
        )->flatMap(
            $this->success->item(fn($v)=>$v->item->getOrElse(null))
        )->flatMap(
            $this->success->sizes(fn($v)=>$v->sizes->getOrElse(Each::t([])))
        )->flatMap(
            fn($v)=>$this->success
        )->getOrElse($this->failure);
    }
}