<?php
namespace saso\item;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class ListContentsPresenter implements Presenter
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
            fn($v)=>$v->items->flatMap(fn($i)=>iterator_to_array($i))
        )->filter(
            fn($fs)=>!empty($fs)
        )->flatMap(
            $this->success->insides(
                fn($v)=>$v
            )
        )->flatMap(
            fn($v)=>$this->success
        )->getOrElse($this->failure);
    }
}
