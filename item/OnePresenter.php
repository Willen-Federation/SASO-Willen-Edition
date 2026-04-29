<?php
namespace saso\item;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class OnePresenter implements Presenter
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
                $this->success->archive(fn($v)=>$v->archive->getOrElseThrow('archive not found'))
            )->flatMap(
                $this->success->quantityLogsGen(fn($v)=>$v->quantityLogsGen->getOrElse([]))
            )->flatMap(
                $this->success->labelSheetsAmount(fn($v)=>$v->labelSheetsAmount)
            )->flatMap(
                $this->success->labelSheetsAmountMax(fn($v)=>$v->labelSheetsAmountMax)
            )->flatMap(
                $this->success->color(fn($v)=>$v->color->getOrElse(''))
            )->flatMap(
                $this->success->size(fn($v)=>$v->size->getOrElse(''))
            )->flatMap(
                $this->success->action(fn($v)=>$v->action->getOrElse(''))
            );
            return $result->isRight() ? $this->success : $this->failure;
        } catch (\Exception $e) {
            return $this->failure;
        }
    }
}