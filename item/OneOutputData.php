<?php
namespace saso\item;

use saso\framework\DTO;
use saso\framework\Getter;
use saso\util\monad\Either;

final class OneOutputData implements DTO
{
    use Getter;
    public function __construct(
        private Either $item,
        private Either $archive,
        private Either $quantityLogsGen,
        private int $labelSheetsAmount,
        private int $labelSheetsAmountMax,
        private Either $color,
        private Either $size,
        private Either $action,
    )
    {
    }
}
