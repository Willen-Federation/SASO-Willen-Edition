<?php
namespace saso\item;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class ItemPresenter implements Presenter
{
    public function __construct(
        private View $success,
        private View $failure,
    )
    {
    }
    public function complete(Either $output): View
    {
        $result = $output->flatMap(
            $this->success->item(fn($v)=>$v)
        );
        return $result->isRight() ? $this->success : $this->failure;
    }
}