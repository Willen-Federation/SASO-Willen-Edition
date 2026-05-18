<?php
namespace saso\repository\item;

use saso\entity\Item;
use saso\repository\DbPrepare;

final class ChangeStatus implements DbPrepare
{
    private array $data;
    public function __construct(
        private Item $item,
        private string $status,
        private \DateTime $now,
    )
    {
    }
    public function getQuery(): string
    {
        return '
            UPDATE Item
                SET   status = :status
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
            'status'   => $this->status,
            'updateAt' => $this->now->format('Y-m-d H:i:s'),
            'concatId' => $this->item->id,
        ];
        return fn()=>$this->item;
    }
}
