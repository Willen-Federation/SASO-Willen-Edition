<?php
namespace saso\repository\itemVar;

use saso\entity\ItemVar;
use saso\repository\DbPrepare;

final class ChangePrice implements DbPrepare
{
    private array $data;
    public function __construct(
        private ItemVar $itemVar,
    )
    {
    }
    public function getQuery(): string
    {
        return '
            UPDATE Item
                SET   price = :price
                    , updateAt = :updateAt
                WHERE concatId = :concatId
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
            'price'=>$this->itemVar->price,
            'updateAt'=>$this->itemVar->updateAt->format('Y-m-d H:i:s'),
            'concatId'=>$this->itemVar->item->id,
        ];
        return fn()=>$this->itemVar;
    }
}
