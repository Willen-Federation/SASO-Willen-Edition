<?php
namespace saso\feature;

use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\Getter;
use saso\util\monad\Either;

/** 
 * @property Either<string> $kind
 * @property Either<int> $amount
 */
final class AmountController implements GettableController, DTO
{
    use Getter;
    private Either $kind;
    private Either $amount;
    public function __construct(
        array $post,
        private \DateTime $now,
    )
    {
        $this->kind = Either::fromNullable(filter_var(
            $post['kind']??'',
            \FILTER_CALLBACK,
            [
                'options'=>fn($v)=>in_array(
                    $v,
                    ['inventory', 'stock', 'shipment']
                )?$v:false
            ]
        ));
        $this->amount = Either::fromNullable(filter_var(
            $post['amount']??'',
            \FILTER_VALIDATE_INT,
            [
                'options'=>[
                    'default'=>false,
                    'min_range'=>0,
                    'max_range'=>9999,
                ]
            ]
        ));
    }
}