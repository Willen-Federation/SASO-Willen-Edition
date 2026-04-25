<?php
namespace saso\item;

use saso\entity\Color;
use saso\entity\Item;
use saso\entity\ItemVar;
use saso\entity\Size;
use saso\framework\DTO;
use saso\framework\Output;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\category;
use saso\repository\Finder;
use saso\util\Each;
use saso\util\monad\Either;

final class RegisterConfirmUsecase implements Usecase
{
    use Output;
    private DTO $output;
    public function __construct(
        private Finder $finder,
        private Presenter $presenter,
    )
    {
    }
    public function handle(DTO $input): void
    {
        try {
            $category = $input->categoryId->flatMap(
                fn($v)=>$this->finder->current(new category\FindOneById(), ['id'=>$v])
            )->flatMap(
                fn($v)=>$v->id
            );
            $item = $input->name->flatMap(
                fn($v)=>new Item(
                    '',
                    $v,
                    $input->pla,
                    $input->plaNote->getOrElseThrow('invalid pla note.'),
                    $input->paper,
                    $input->paperNote->getOrElseThrow('invalid paper note.'),
                    new \DateTime(),
                )
            );
            $colors = fn($i)=>$input->colors->flatMap(
                Each::tf(fn($v)=>new Color(
                    $i,
                    $v->code->getOrElseThrow('invalid color code.'),
                    $v->name->getOrElseThrow('invalid color name.')
                ))
            )->orElse(fn($v)=>throw new \Exception('color is nothing.'));
            $sizes = fn($i)=>$input->sizes->flatMap(
                Each::tf(fn($v)=>new Size(
                    $i,
                    $v->code->getOrElseThrow('invalid size code.'),
                    $v->name->getOrElseThrow('invalid size name.'),
                    $v->orderNumber->getOrElseThrow('invalid size order number.')
                ))
            )->orElse(fn($v)=>throw new \Exception('size is nothing.'));
            $itemVar = fn($i)=>new ItemVar(
                $i,
                $category->getOrElse(null),
                $input->price->getOrElse(0),
                new \DateTime(),
            );
            $this->output = $item->flatMap(
                fn($v)=> new RegisterOutputData(
                    $v,
                    $colors($v),
                    $sizes($v),
                    $itemVar($v),
                    $input->validFeaturesAmount,
                )
            )->getOrElseThrow(
                'invalid item.'
            );
        } catch (\Exception $e) {
            $this->output = new RegisterConfirmErrorOutput(Either::left($e->getMessage()));
        }
    }
}