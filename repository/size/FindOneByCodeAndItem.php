<?php
namespace saso\repository\size;

use saso\entity\Size;
use saso\entity\Item;
use saso\repository\DbPrepare;
use saso\util\Each;

final class FindOneByCodeAndItem implements DbPrepare
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
                WHERE concatId = :concatId
                    AND sizeCode = :sizeCode
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':concatId', $this->item->id);
        $stmt->bindValue(':sizeCode', $input['code']);
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
