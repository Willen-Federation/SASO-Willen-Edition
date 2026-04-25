<?php
namespace saso\repository\color;

use saso\entity\Color;
use saso\entity\Item;
use saso\repository\DbPrepare;
use saso\util\Each;

final class FindOneWithImageTypeByCodeAndItem implements DbPrepare
{
    public function __construct(
        private Item $item,
    )
    {
    }
    public function getQuery(): string
    {
        return '
            SELECT concatId, colorName, colorCode, imageType
                FROM Color
                WHERE concatId = :concatId
                    AND colorCode = :colorCode
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':concatId', $this->item->id);
        $stmt->bindValue(':colorCode', $input['code']);
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>new Color(
            $this->item,
            $v->colorCode,
            $v->colorName,
            $v->imageType==='null'?null:$v->imageType,
        ));
    }
}
