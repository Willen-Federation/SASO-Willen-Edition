<?php
namespace saso\item;

use saso\framework\Presenter;
use saso\framework\View;
use saso\util\monad\Either;

final class RegisterConfirmPresenter implements Presenter
{
    public function __construct(
        private View $success,
        private View $error,
    )
    {
    }
    public function complete(Either $output): View
    {
        try {
            $colors = $output->flatMap(
                fn($v)=>$v->colors->flatMap(
                    fn($cs)=>iterator_to_array($cs)
                )
            );
            $inputColors = $colors->flatMap(
                fn($cs)=>implode(', ', array_map(
                    fn($c)=>$c->name,
                    $cs,
                ))
            )->getOrElse('');
            $serializedColors = $colors->flatMap(
                fn($cs)=>implode(', ', array_map(
                    fn($c)=>$c->name.'('.$c->code.')',
                    $cs,
                ))
            )->getOrElse('');
            $sizes = $output->flatMap(
                fn($v)=>$v->sizes->flatMap(
                    fn($ss)=>iterator_to_array($ss)
                )
            );
            $inputSizes = $sizes->flatMap(
                fn($ss)=>implode(', ', array_map(
                    fn($s)=>$s->name,
                    $ss,
                ))
            )->getOrElse('');
            $serializedSizes = $sizes->flatMap(
                fn($ss)=>implode(',', array_map(
                    fn($s)=>$s->name,
                    $ss,
                ))
            )->getOrElse('');

            return $output->flatMap(
                fn($v)=>$v->item
            )->flatMap(
                fn($v)=>$output
            )->flatMap(
                $this->success->item(fn($v)=>$v->item),
            )->flatMap(
                $this->success->itemVar(fn($v)=>$v->itemVar),
            )->flatMap(
                $this->success->inputColors(fn($v)=>$inputColors)
            )->flatMap(
                $this->success->serializedColors(fn($v)=>$serializedColors)
            )->flatMap(
                $this->success->inputSizes(fn($v)=>$inputSizes)
            )->flatMap(
                $this->success->serializedSizes(fn($v)=>$serializedSizes)
            )->flatMap(
                $this->success->validFeaturesAmount(fn($v)=>$v->validFeaturesAmount)
            )->flatMap(
                fn($v)=>$this->success
            )->orElse(
                function($v) {
                    return Either::of($this->error->errorMessage(fn($i)=>$v)($this->error));
                }
            )->getOrElseThrow('view failure.');
        } catch (\Exception $e) {
            return $this->error->errorMessage(fn($v)=>$e->getMessage())($this->error);
        }
    }
}
