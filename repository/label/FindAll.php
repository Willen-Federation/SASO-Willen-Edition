<?php
namespace saso\repository\label;

use saso\entity\Label;
use saso\repository\DbPrepare;
use saso\util\Each;

final class FindAll implements DbPrepare
{
    public function __construct(
    )
    {
    }
    public function getQuery(): string
    {
        return '
            SELECT *
                FROM Label
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
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
