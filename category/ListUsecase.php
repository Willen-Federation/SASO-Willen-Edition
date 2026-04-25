<?php
namespace saso\category;

use saso\entity\Category;
use saso\framework\DTO;
use saso\framework\Output;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\category\FindChildrenByParent;
use saso\repository\category\FindOneById;
use saso\repository\category\FindOneParent;
use saso\repository\category\FindRoots;
use saso\repository\Finder;
use saso\util\Each;

final class ListUsecase implements Usecase
{
    use Output;
    private DTO $output;
    public function __construct(
        private Finder $finder,
        private Presenter $presenter,
    )
    {
    }
    public function handle(DTO $data): void
    {
        $root = $data->id->flatMap(
            fn($v)=>$this->finder->current(new FindOneById(), ['id'=>$v])
        )->flatMap(
            fn($v)=>$v->setChildren($this->finder->generate(new FindChildrenByParent($v)))
        )->flatMap(
            $this->climb(...)
        );
        $tree = $this->finder->generate(new FindRoots())->flatMap(Each::tf(
            fn($v)=>$root->filter(
                fn($r)=>$r->id===$v->id
            )->getOrElse($v)
        ));
        $this->output = new ListOutput(
            $tree,
            $data->id,
        );
    }
    private function climb(Category $category): Category
    {
        $parent = $this->finder->current(new FindOneParent($category));
        $children = $parent->flatMap(
            fn($v)=>$this->finder->generate(new FindChildrenByParent($v))
        )->flatMap(Each::tf(
            fn($v)=>$v->id===$category->id?$category:$v
        ));
        return $parent->flatMap(
            fn($v)=>$v->setChildren($children)
        )->flatMap(
            $this->climb(...)
        )->getOrElse($category);
    }
}