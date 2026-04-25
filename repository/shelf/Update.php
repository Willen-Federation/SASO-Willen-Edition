<?php
namespace saso\repository\shelf;

use saso\entity\Shelf;
use saso\repository\DbPrepare;

final class Update implements DbPrepare
{
    private array $data;
    public function __construct(
        private Shelf $shelf,
    )
    {
    }
    public function getQuery(): string
    {
        return '
            UPDATE Shelf
                SET shelfNumber = :shelfNumber
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
            'detaleCode'=>$this->shelf->feature->item->id.$this->shelf->feature->getCode(),
            'shelfNumber'=>$this->shelf->number,
        ];
        return fn()=>$this->shelf;
    }
}
