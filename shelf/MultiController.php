<?php
namespace saso\shelf;

use saso\framework\Controller;
use saso\framework\DirectInput;
use saso\framework\DTO;
use saso\framework\Getter;
use saso\util\monad\Either;
use saso\util\monad\Maybe;

/**
 * @property Either<int> $page
 * @property int $dimensionAmount
 * @property array<Either<string>> $mins
 * @property array<Maybe<int>> $maxs
 */
final class MultiController implements Controller, DTO
{
    use DirectInput;
    use Getter;
    private Either $page;
    private int $dimensionAmount;
    private array $mins;
    private array $maxs;
    public function __construct(
        array $query,
    )
    {
        $this->page = Either::fromNullable(filter_var(
            $query['page']??'',
            \FILTER_VALIDATE_INT,
            [
                'options'=>[
                    'default'=>false,
                    'min_range'=>1,
                    'max_range'=>100000000,
                ]
            ],
        ));
        $this->dimensionAmount = 1;
        for($i = 1; $i <= 5; $i++) {
            if(($query['min'.$i]??'')==='') {
                break;
            }
            $this->dimensionAmount = $i;
        }
        $min = fn($dimension)=>Either::fromNullable(filter_var(
            $query['min'.$dimension]??'',
            \FILTER_VALIDATE_REGEXP,
            [
                'options'=>[
                    'default'=>false,
                    'regexp'=>'/^[0-9A-Za-z]{1,2}$/'
                ]
            ],
        ))->flatMap(
            fn($v)=>strtoupper($v)
        );
        $max = fn($dimension)=>Maybe::fromNullable(filter_var(
            $query['max'.$dimension]??'',
            \FILTER_VALIDATE_INT,
            [
                'options'=>[
                    'default'=>false,
                    'min_range'=>0,
                    'max_range'=>99,
                ]
            ],
        ));
        $this->mins = array_map(
            fn($d)=>$min($d),
            range(1,$this->dimensionAmount)
        );
        $this->maxs = array_map(
            fn($d)=>$max($d),
            range(1,$this->dimensionAmount)
        );
    }
}

