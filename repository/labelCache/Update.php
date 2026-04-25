<?php
namespace saso\repository\labelCache;

use saso\entity\LabelCache;
use saso\repository\DbPrepare;

final class Update implements DbPrepare
{
    private array $data;
    public function __construct(
        private LabelCache $labelCache,
    )
    {
    }
    public function getQuery(): string
    {
        return '
            UPDATE LabelCache
                SET sheetsAmount = :sheetsAmount
                WHERE detaleCode = :detaleCode
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        foreach(array_keys($this->data) as $prop) {
            $stmt->bindValue(':'.$prop, $this->data[$prop]);
        }
    }
    public function map(): \Closure
    {
        $this->data = [
            'detaleCode'=>$this->labelCache->feature->getFullCode(),
            'sheetsAmount'=>$this->labelCache->amount,
        ];
        return fn()=>$this->labelCache;
    }
}
