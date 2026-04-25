<?php
namespace saso\item;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class AddFeaturePresenter implements Presenter
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
            fn($v)=>$v->item
        )->flatMap(
            $this->success->item(fn($v)=>$v),
        )->flatMap(
            fn($v)=>$output
        )->flatMap(
            $this->success->inputColors(fn($v)=>$v->colors->join()->flatMap(
                fn($cs)=>implode(', ', array_map(
                    fn($c)=>$c->name,
                    $cs,
                ))
            )->getOrElse(''))
        )->flatMap(
            $this->success->serializedColors(fn($v)=>$v->colors->join()->flatMap(
                fn($cs)=>implode(',', array_map(
                    fn($c)=>$c->name.'('.$c->code.')',
                    $cs,
                ))
            )->getOrElse(''))
        )->flatMap(
            $this->success->inputSizes(fn($v)=>$v->sizes->join()->flatMap(
                fn($ss)=>implode(', ', array_map(
                    fn($s)=>$s->name,
                    $ss,
                ))
            )->getOrElse(''))
        )->flatMap(
            $this->success->serializedSizes(fn($v)=>$v->sizes->join()->flatMap(
                fn($ss)=>implode(',', array_map(
                    fn($s)=>$s->name,
                    $ss,
                ))
            )->getOrElse(''))
        )->flatMap(
            $this->success->isValidAmount(fn($v)=>$v->isValidAmount)
        )->flatMap(
            fn($v)=>$this->success
        )->getOrElse($this->failure);
    }
}