<?php
namespace saso\label;

use saso\entity\Feature;
use saso\entity\Pdf;
use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\feature\FindOneByFullcode;
use saso\repository\Finder;
use saso\repository\label\FindOneByName;
use saso\util\monad\Either;

final class ShortNameUsecase implements Usecase
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
        $label = $data->name->flatMap(
            fn($v)=>$this->finder->current(new FindOneByName(), ['name'=>$v])
        );
        $feature = $data->fullCode->flatMap(
            fn($v)=>$this->finder->current(
                new FindOneByFullcode($this->finder),
                Feature::substrFullCode($v)
            )
        );
        $this->output = Pdf::shortenName($label, $feature);
    }
}