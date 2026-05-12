<?php
namespace saso\itemAttribute;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class ListPresenter implements Presenter
{
    public function __construct(
        private View $success,
    ) {
    }

    public function complete(Either $output): View
    {
        return $output->flatMap(
            $this->success->definitions(
                fn($v) => $v->definitions->flatMap(
                    fn($gen) => iterator_to_array($gen)
                )->getOrElse([])
            )
        )->flatMap(
            fn($v) => $this->success
        )->getOrElse($this->success);
    }
}
