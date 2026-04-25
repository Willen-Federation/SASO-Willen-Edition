<?php
namespace saso\item;

use saso\entity\Config;
use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\Getter;
use saso\util\monad\Either;

final class OneController implements GettableController, DTO
{
    use Getter;
    private Either $action;
    private int $sheetAmount;
    public function __construct(
        array $query,
        array $config,
    )
    {
        $this->action = Either::fromNullable($query['action']??false)->filter(
            fn($v)=>in_array($v, ['stock', 'shipment','inventory', 'shelf', 'label'])
        );
        $this->sheetAmount = Config::sheetAmountConstraint($config);
    }
}
