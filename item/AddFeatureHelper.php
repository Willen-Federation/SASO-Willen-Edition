<?php
namespace saso\item;

use saso\entity;
use saso\framework\DTO;
use saso\util\monad\Either;
use saso\util\monad\Maybe;

final class AddFeatureHelper
{
    public static function output(Either $item, Either $oldColors, Either $oldSizes, Maybe $newColors, Maybe $newSizes): DTO
    {
        $amount = fn($v)=>$v->join()->flatMap(
            fn($v)=>count($v)
        )->getOrElse(0);
        return new AddFeatureOutput(
            $item,
            self::createNewColors($item, $oldColors, $newColors),
            self::createNewSizes($item, $oldSizes, $newSizes),
            entity\Feature::validFeaturesAmount(
                ($amount($oldColors)+$amount($newColors))
                *
                ($amount($oldSizes)+$amount($newSizes))
            )
        );
    }
    private static function createNewColors(Either $item, Either $olds, Maybe $news): Maybe
    {
        $safeCodes = self::continueIndex($olds, $news)->join();    
        return $news->map(
            fn($e)=>$item->flatMap(
                fn($i)=>$e->flatMap(
                    fn($a)=>array_map(
                        fn($v)=>fn($code)=>new entity\Color(
                            $i,
                            $code,
                            $v,
                        ),
                        $a
                    )
                )
            )->flatMap(
                fn($a)=>$safeCodes->flatMap(
                    fn($code)=>array_map(
                        fn($index)=>$a[$index]($code[$index]),
                        range(0, count($a)-1)
                    )
                )
            )->join()
        );
    }
    private static function createNewSizes(Either $item, Either $olds, Maybe $news): Maybe
    {
        $safeCodes = self::continueIndex($olds, $news)->join();
        try {
            $newOrderNumber = $olds->flatMap(
                fn($v)=>array_reduce(
                    $v,
                    fn($carry, $item)=>max($item->orderNumber, $carry),
                    0
                )
            )->flatMap(
                fn($v)=>fn($index)=>entity\Size::orderNumberConstraint(($v+$index+1)%100)->getOrElseThrow(
                    'invalid order number.'
                )
            );    
        } catch (\Exception $e) {
            return Maybe::of(Either::left($e->getMessage()));
        }
        return $news->map(
            fn($e)=>$item->flatMap(
                fn($i)=>$e->flatMap(
                    fn($a)=>array_map(
                        fn($v)=>fn($code)=>fn($orderNumber)=>new entity\Size(
                            $i,
                            $code,
                            $v,
                            $orderNumber,
                        ),
                        $a
                    )
                )
            )->flatMap(
                fn($a)=>$safeCodes->flatMap(
                    fn($code)=>$newOrderNumber->flatMap(
                    fn($orderNumber)=>array_map(
                        fn($index)=>$a[$index]($code[$index])($orderNumber($index)),
                        range(0, count($a)-1)
                    ))
                )
            )->join()
        );
    }
    private static function continueIndex(Either $olds, Maybe $news): Maybe
    {
        $oldAmount = $olds->flatMap(
            fn($v)=>count($v)
        )->getOrElse(0);
        try {
            return $news->map(
                fn($e)=>$e->flatMap(
                fn($a)=>array_map(
                    fn($index)=>entity\Feature::validateCode($oldAmount+$index)->getOrElseThrow('invalid code.'),
                    range(0, count($a)-1)
                )
            ));
        } catch (\Exception $e) {
            return Maybe::of(Either::left($e->getMessage()));
        }
    }
}