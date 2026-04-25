<?php
namespace saso\shelf;

use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\util\monad\Either;

final class MultiUsecase implements Usecase
{
    use OutputForSingleEntity;
    private Either $output;
    public function __construct(
        private Presenter $presenter,
    )
    {
    }
    public function handle(DTO $data): void
    {
        try {
            $mins = array_map(
                fn($v)=>$v->getOrElseThrow('invalid mins input.'),
                $data->mins
            );
            $maxsSmallable = array_map(
                fn($v)=>$v->getOrElse(''),
                $data->maxs
            );
            $maxs = array_reduce(
                range(0, count($mins)-1),
                fn($carry, $item)=>[...$carry, $mins[$item]<=$maxsSmallable[$item]?$maxsSmallable[$item]:''],
                []
            );
            $aDigitRadix = fn(int $dimension)=>
                $maxs[$dimension-1] === ''
                || !is_numeric($mins[$dimension-1])?
                $mins[$dimension-1]:
                $maxs[$dimension-1] + 1
                - $mins[$dimension-1];
            $shelfPerPage = 100;
            $pagesAmount = ShelfNumbersCalc::pagesAmount(
                $data->dimensionAmount,
                $aDigitRadix,
                $shelfPerPage
            );
            $this->output = $data->page->filter(
                fn($v)=>$v <= $pagesAmount
            )->flatMap(
                fn($v)=>new MultiOutput(
                    $pagesAmount,
                    ShelfNumbersCalc::calcShelfNumbers(
                        $data->dimensionAmount,
                        $aDigitRadix,
                        $shelfPerPage,
                        $v,
                        $mins,
                    ),
                    $v,
                    $mins,
                    $maxs,
                )
            )->orElse(
                fn($v)=>Either::left('invalid page.')
            );
        } catch (\Exception $e) {
            $this->output = Either::left($e->getMessage());
        }
    }
}