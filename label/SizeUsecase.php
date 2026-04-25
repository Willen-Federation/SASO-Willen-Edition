<?php
namespace saso\label;

use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\label\FindOneByName;
use saso\repository\Finder;
use saso\util\monad\Either;

final class SizeUsecase implements Usecase
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
        $this->output = $data->name->flatMap(
            fn($v)=>$this->finder->current(new FindOneByName(), ['name'=>$v])
        );
    }
}