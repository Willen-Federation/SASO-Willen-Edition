<?php
namespace saso\shelf;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class MultiPresenter implements Presenter
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
            $this->success->pagesAmount(fn($v)=>$v->pagesAmount)
        )->flatMap(
            $this->success->shelves(fn($v)=>$v->shelves)
        )->flatMap(
            $this->success->page(fn($v)=>$v->page)
        )->flatMap(
            $this->success->mins(fn($v)=>$v->mins)
        )->flatMap(
            $this->success->maxs(fn($v)=>$v->maxs)
        )->flatMap(
            fn($v)=>$this->success
        )->orElse(
            fn($v)=>Either::left($this->failure->errorMessage(fn($e)=>$e)($v))
        )->getOrElse($this->failure);
    }
}