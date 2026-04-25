<?php
namespace saso\item;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class RowPresenter implements Presenter
{
    public function __construct(
        private View $success,
    )
    {
    }
    public function complete(Either $output): View
    {
        return $output->flatMap(
            $this->success->item(fn($v)=>$v->item)
        )->flatMap(
            $this->success->iv(fn($v)=>$v->iv->getOrElse(null))
        )->flatMap(
            $this->success->colors(fn($v)=>$v->colors->getOrElse(null))
        )->flatMap(
            $this->success->sizes(fn($v)=>$v->sizes->getOrElse(null))
        )->flatMap(
            fn($v)=>$this->success
        )->getOrElse($this->success);
    }
}