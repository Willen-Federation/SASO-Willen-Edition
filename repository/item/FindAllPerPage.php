<?php
namespace saso\repository\item;

use saso\entity\Item;
use saso\repository\DbPrepare;
use saso\util\Each;

final class FindAllPerPage implements DbPrepare
{
    private string $like;
    public function __construct(
        private string $sortColumn,
        private string $direction,
        private string $search,
    )
    {
        $this->like = empty($this->search)?'':'AND itemName LIKE :itemName';
    }
    public function getQuery(): string
    {
        return '
            SELECT dateCode, serial, itemName, pla, plaNote, paper, paperNote, createAt, concatId, status
                FROM Item
                WHERE archive = :archive '.$this->like.'
                ORDER BY '.$this->sortColumn.' '. strtoupper($this->direction) .'
                LIMIT :limit
                OFFSET :offset
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':archive', $input['archive'], \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $input['limit'], \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $input['offset'], \PDO::PARAM_INT);
        if(!empty($this->search)) {
            $stmt->bindValue(':itemName', '%'.$this->search.'%', \PDO::PARAM_STR);
        }
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>new Item(
            $v->serial,
            $v->itemName,
            $v->pla,
            $v->plaNote,
            $v->paper,
            $v->paperNote,
            new \DateTime($v->createAt),
            $v->status ?? null,
        ));
    }
}
