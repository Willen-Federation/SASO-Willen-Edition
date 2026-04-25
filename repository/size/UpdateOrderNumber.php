<?php
namespace saso\repository\size;

use saso\entity\Size;
use saso\repository\DbPrepare;

final class UpdateOrderNumber implements DbPrepare
{
    private array $data;
    public function __construct(
        private Size $size,
    )
    {
    }
    public function getQuery(): string
    {
        return '
            UPDATE Size
                SET orderNumber = :orderNumber
                WHERE concatId = :concatId
                AND sizeCode = :sizeCode
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
            'concatId'=>$this->size->item->id,
            'sizeCode'=>$this->size->code,
            'orderNumber'=>$this->size->orderNumber,
        ];
        return fn()=>$this->size;
    }
}
