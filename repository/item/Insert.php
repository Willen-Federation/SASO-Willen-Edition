<?php
namespace saso\repository\item;

use saso\entity\Item;
use saso\repository\DbPrepare;

final class Insert implements DbPrepare
{
    private array $data;
    public function __construct(
        private Item $item,
    )
    {
    }
    public function getQuery(): string
    {
        return '
            INSERT INTO Item('
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
            'dateCode'=>$this->item->dateCode,
            'serial'=>$this->item->serial,
            'itemName'=>$this->item->name,
            'pla'=>$this->item->pla?1:0,
            'plaNote'=>$this->item->plaNote,
            'paper'=>$this->item->paper?1:0,
            'paperNote'=>$this->item->paperNote,
            'createAt'=>$this->item->createAt->format('Y-m-d H:i:s'),
            'concatId'=>$this->item->id,
        ];
        return fn()=>$this->item;
    }
}
