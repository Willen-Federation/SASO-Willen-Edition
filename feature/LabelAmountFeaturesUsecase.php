<?php
namespace saso\feature;

use saso\entity\Feature;
use saso\entity\LabelCache;
use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\feature\FindOneByFullcode;
use saso\repository\labelCache\FindAll;
use saso\repository\Finder;
use saso\util\Each;
use saso\util\monad\Either;

final class LabelAmountFeaturesUsecase implements Usecase
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
        $this->output = $this->finder->generate(new FindAll())->flatMap(Each::tf(
            fn($v)=>$this->finder->current(
                new FindOneByFullcode($this->finder),
                Feature::substrFullCode($v['fullCode'])
            )->flatMap(
                fn($f)=>new LabelCache($f, $v['amount'])
            )
        ))->flatMap(Each::tf(
            fn($v)=>$v->flatMap(
                fn($l)=>$l->feature
            )
        ))->flatMap(Each::tf(
            fn($v)=>$v->flatMap(fn($f)=>Each::t(array_fill(0, $f->labelAmount, $f)))->getOrElse(Each::t([]))
        ))->flatMap(Each::m());
    }
}