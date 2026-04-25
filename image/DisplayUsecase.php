<?php
namespace saso\image;

use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\color\FindOneWithImageByCodeAndItem;
use saso\repository\item\FindOneById;
use saso\repository\Finder;
use saso\util\monad\Either;

final class DisplayUsecase implements Usecase
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
            fn($v)=>$this->finder->current(new FindOneById(), ['id'=>$v])
        )->flatMap(
            fn($v)=>$data->color->flatMap(
            fn($c)=>$this->finder->current(new FindOneWithImageByCodeAndItem($v), [
                'code'=>$c
            ])
        ));
    }
}