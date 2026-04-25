<?php
namespace saso\feature;

use saso\entity\Config;
use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\Getter;
use saso\util\monad\Either;

final class LabelController implements GettableController, DTO
{
    use Getter;
    private int $sheetsMax;
    private Either $amount;
    public function __construct(
        private array $post,
        private array $config,
    )
    {
        $this->sheetsMax = Config::sheetAmountConstraint($config);
        $this->amount = Either::fromNullable(filter_var(
            $post['amount']??'',
            \FILTER_VALIDATE_INT,
            [
                'options'=>[
                    'default'=>false,
                    'min_range'=>1,
                    'max_range'=>$this->sheetsMax
                ]
            ],
        ));
    }
}
