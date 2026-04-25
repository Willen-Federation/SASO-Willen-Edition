<?php
namespace saso\repository\size;

use saso\entity\Size;
use saso\repository\DbPrepare;

final class Insert implements DbPrepare
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
            INSERT INTO Size('
                .implode(',', array_keys($this->data)).
            ')
            VALUES ('
                .implode(',', array_map(fn($p)=>':'.$p, array_keys($this->data))).
            ')
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
            'sizeName'=>$this->size->name,
            'orderNumber'=>$this->size->orderNumber,
        ];
        return fn()=>$this->size;
    }
}
