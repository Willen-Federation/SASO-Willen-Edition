<?php
namespace saso\category;

use saso\entity\Category;
use saso\framework\Presenter;
use saso\framework\View;
use saso\util\Each;
use saso\util\monad\Either;

final class ListPresenter implements Presenter
{
    public function __construct(
        private View $success,
    )
    {
    }
    public function complete(Either $output): View
    {
        return $output->flatMap(
            $this->success->tree(
                fn($v)=>$v->tree->flatMap(
                    Each::tf(self::down(...))
                )->flatMap(
                    fn($v)=>iterator_to_array($v)
                )->getOrElse([])
            )
        )->flatMap(
            $this->success->clicked(
                fn($v)=>$v->clicked->getOrElse(null)
            )
        )->flatMap(
            fn($v)=>$this->success
        )->getOrElse($this->success);
    }
    public static function down(Category $category): array
    {
        return [
            'key'=>$category->id,
            'name'=>$category->name,
            'children'=>$category->children->flatMap(
                Each::tf(self::down(...))
            )->flatMap(
                fn($i)=>iterator_to_array($i)
            )->getOrElse([])
        ];
    }
}