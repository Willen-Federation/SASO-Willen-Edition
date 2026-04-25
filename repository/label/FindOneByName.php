<?php
namespace saso\repository\label;

use saso\entity\Label;
use saso\repository\DbPrepare;
use saso\util\Each;

final class FindOneByName implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            SELECT *
                FROM Label
                WHERE labelName = ?
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(1, $input['name']);
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>new Label(
            $v->labelName,
            $v->marginTop,
            $v->marginLeft,
            $v->width,
            $v->height,
            $v->intervalColomn,
            $v->intervalRow,
        ));
    }
}
