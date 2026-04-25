<?php
namespace saso\image;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class StartPresenter implements Presenter
{
    public function __construct(
        private View $success,
        private View $failure,
    )
    {
    }
    public function complete(Either $output): View
    {
        try{
            return $output->flatMap(
                $this->success->archive(fn($v)=>$v->archive->getOrElseThrow('item not found.'))
            )->flatMap(
                fn($v)=>$v->color
            )->flatMap(
                $this->success->item(fn($v)=>$v->item)
            )->flatMap(
                $this->success->color(fn($v)=>$v)
            )->flatMap(
                fn($v)=>$this->success
            )->getOrElse($this->failure);
        } catch (\Exception $e) {
            return $this->failure;
        }
    }
}