<?php
namespace saso\item;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class AttributeValuesPresenter implements Presenter
{
    public function __construct(
        private View $success,
        private View $failure,
    ) {
    }

    public function complete(Either $output): View
    {
        return $output->flatMap(
            $this->success->item(fn($v) => $v->item->getOrElse(null))
        )->flatMap(
            $this->success->attributes(fn($v) => $v->attributes->getOrElse([]))
        )->flatMap(
            fn($v) => $this->success
        )->orElse(
            fn($v) => Either::of($this->failure)
        )->getOrElse($this->failure);
    }
}
