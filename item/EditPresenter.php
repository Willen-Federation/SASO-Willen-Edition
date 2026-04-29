<?php
namespace saso\item;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class EditPresenter implements Presenter
{
    public function __construct(
        private View $success,
        private View $failure,
    )
    {
    }
    public function complete(Either $output): View
    {
        try {
            $result = $output->flatMap(
                $this->success->item(fn($v)=>$v->item->getOrElseThrow('item not found'))
            )->flatMap(
                $this->success->itemVar(fn($v)=>$v->itemVar->getOrElseThrow('item not found'))
            );
            return $result->isRight() ? $this->success : $this->failure;
        } catch (\Exception $e) {
            return $this->failure;
        }
    }
}
