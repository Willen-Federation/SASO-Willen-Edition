<?php
namespace saso\repository\item;

use saso\repository\DbPrepare;
use saso\util\Each;

final class CountAll implements DbPrepare
{
    private string $like;
    public function __construct(
        private string $itemName,
        private bool $isArchive,
    )
    {
        $this->like = empty($this->itemName)?'':'AND itemName LIKE :itemName';
    }
    public function getQuery(): string
    {
        return '
            SELECT COUNT(concatId) AS amount

                FROM Item
                WHERE archive = :archive
        '.$this->like;
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':archive', $this->isArchive?1:0, \PDO::PARAM_INT);
        if(!empty($this->itemName)) {
            $stmt->bindValue(':itemName', '%' . $this->itemName . '%', \PDO::PARAM_STR);
        }
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>$v->amount);
    }
}
