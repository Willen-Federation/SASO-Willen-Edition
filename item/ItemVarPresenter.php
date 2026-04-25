<?php
namespace saso\item;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class ItemVarPresenter implements Presenter
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
            $this->success->itemVar(fn($v)=>$v)
        )->flatMap(
            $this->success->item(fn($v)=>$v->item)
        )->flatMap(
            fn($v)=>$this->success
        )->getOrElse($this->failure);
    }
}