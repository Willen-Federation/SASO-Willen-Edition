<?php
namespace saso\category;

use saso\entity;
use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\category;
use saso\repository\Finder;
use saso\util\monad\Either;

final class PathUsecase implements Usecase
{
    use OutputForSingleEntity;
    private Either $output;
    public function __construct(
        private Finder $finder,
        private Presenter $presenter,
    )
    {
    }
    public function handle(DTO $data): void
    {
        $this->output = $data->id->flatMap(
            fn($v)=>$this->finder->current(new category\FindOneById(), ['id'=>$v])
        )->flatMap(
            $this->findPath(...)
        );
    }
    private function findPath(entity\Category $leaf): string
    {
        return $this->finder->current(
            new category\FindOneParentById(),
            ['id'=>$leaf->id]
        )->flatMap(
            $this->findPath(...)
        )->getOrElse('').'/'.$leaf->name;
    }
}