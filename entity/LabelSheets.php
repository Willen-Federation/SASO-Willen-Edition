<?php
namespace saso\entity;

final class LabelSheets
{
    public function __construct(
        private int $sheetsAmountMax,
    )
    {
    }
    /** 
     * @param Feature=>int $sumWithoutAdding
    */
    public function addable(LabelCache $adding, \Closure $sumWithoutAdding): bool
    {
        return $adding->amount + $sumWithoutAdding($adding->feature) <= $this->sheetsAmountMax;
    }
}