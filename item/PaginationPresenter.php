<?php
namespace saso\item;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class PaginationPresenter implements Presenter
{
    public function __construct(
        private View $success,
    )
    {
    }
    public function complete(Either $output): View
    {
        return $output->flatMap(
            $this->success->pageAmount(fn($v)=>$v->pageAmount)
        )->flatMap(
            $this->success->sortBy(fn($v)=>$v->sortby)
        )->flatMap(
            $this->success->direction(fn($v)=>$v->direction)
        )->flatMap(
            $this->success->search(fn($v)=>$v->search)
        )->flatMap(
            $this->success->page(fn($v)=>$v->page)
        )->flatMap(
            fn($v)=>$this->success
        )->getOrElseThrow('pagination error.');
    }
}
