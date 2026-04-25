<?php
namespace saso\shelf;

class ShelfNumbersCalc
{
    public static function pagesAmount(int $dimensionAmount, \Closure $aDigitRadix, int $shelfPerPage): int
    {
        return ceil((self::calcShelvesAmount($dimensionAmount, $aDigitRadix)+1)/$shelfPerPage);
    }
    /** @return array<string> */
    public static function calcShelfNumbers(
        int $dimensionAmount,
        \Closure $aDigitRadix,
        int $shelfPerPage,
        int $page,
        array $mins,
    ): array
    {
        $head = $shelfPerPage*($page-1);
        $shelvesAmount = self::calcShelvesAmount($dimensionAmount, $aDigitRadix);
        return array_map(
            fn($rawShelf)=>implode('-', array_map(
                function($i) use($dimensionAmount, $rawShelf, $aDigitRadix, $mins) {
                    $aShelfNumber = self::aShelfNumber($dimensionAmount, $rawShelf, $aDigitRadix, [])[$i];
                    if(is_string($aShelfNumber)) {
                        return $aShelfNumber;
                    } else {
                        return sprintf('%02d', $aShelfNumber+$mins[$i]);
                    }
                },
                range(0, $dimensionAmount-1)
                
            )),
            range($head, ($shelvesAmount-$head)>=$shelfPerPage?$head+$shelfPerPage-1:$head+$shelvesAmount%$shelfPerPage)
        );
    }
    private static function calcShelvesAmount(int $dimensionAmount, \Closure $aDigitRadix): int
    {
        return array_reduce(
            range(1, $dimensionAmount),
            fn($carry, $item)=>is_string($aDigitRadix($item))?$carry:$carry*$aDigitRadix($item),
            1
        )-1;
    }
    private static function aShelfNumber(int $dimension, int|string $rawShelf, \Closure $aDigitRadix, array $acum): array
    {
        if($dimension == 1) {
            return array_merge([is_string($aDigitRadix($dimension))||is_string($rawShelf)?$aDigitRadix($dimension):$rawShelf%$aDigitRadix($dimension)], $acum);
        }
        return self::aShelfNumber(
            $dimension-1,
            is_string($aDigitRadix($dimension))?$rawShelf:floor($rawShelf/$aDigitRadix($dimension)),
            $aDigitRadix,
            array_merge([is_string($aDigitRadix($dimension))?$aDigitRadix($dimension):$rawShelf%$aDigitRadix($dimension)], $acum)
        );
    }
}
