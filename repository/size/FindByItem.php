<?php
namespace saso\repository\size;

use saso\entity\Item;
use saso\entity\Size;
use saso\repository\DbPrepare;
use saso\util\Each;

final class FindByItem implements DbPrepare
{
    public function __construct(
        private Item $item,
    )
    {
    }
    public function getQuery(): string
    {
        return '
            SELECT *
                FROM Size
                WHERE concatId = ?
                ORDER BY orderNumber
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(1, $this->item->id);
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>new Size(
            $this->item,
            $v->sizeCode,
            $v->sizeName,
            $v->orderNumber,
        ));
    }
}

