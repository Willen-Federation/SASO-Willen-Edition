<?php
namespace saso\repository\item;

use saso\entity\Item;
use saso\repository\DbPrepare;

final class ChangeMeta implements DbPrepare
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
            UPDATE Item
                SET   note = :note
                    , jan_code = :jan_code
                    , isbn = :isbn
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
            'note'=>$this->item->note,
            'jan_code'=>$this->item->janCode,
            'isbn'=>$this->item->isbnCode,
            'concatId'=>$this->item->id,
        ];
        return fn()=>$this->item;
    }
}
