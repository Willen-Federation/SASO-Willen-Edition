<?php
namespace saso\repository\archive;

use saso\entity\Archive;
use saso\entity\Item;
use saso\repository\DbPrepare;
use saso\util\Each;

final class FindOneByItem implements DbPrepare
{
    public function __construct(
        private Item $item,
    )
    {
    }
    public function getQuery(): string
    {
        return '
            SELECT concatId, archive, archiveNote, archiveAt
                FROM Item
                WHERE concatId = ?
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(1, $this->item->id);
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>new Archive(
            $this->item,
            $v->archive===1?true:false,
            $v->archiveNote,
            new \DateTime($v->archiveAt??''),
        ));
    }
}

