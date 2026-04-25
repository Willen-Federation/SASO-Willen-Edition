<?php
namespace saso\repository\color;

use saso\entity\Color;
use saso\entity\Item;
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
            SELECT concatId, colorName, colorCode
                FROM Color
                WHERE concatId = ?
                ORDER BY colorCode
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(1, $this->item->id);
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>new Color(
            $this->item,
            $v->colorCode,
            $v->colorName,
        ));
    }
}
