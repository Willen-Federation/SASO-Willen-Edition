<?php
namespace saso\repository\labelCache;

use saso\repository\DbPrepare;
use saso\util\Each;

final class SumAll implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            SELECT SUM(sheetsAmount) AS sum
                FROM LabelCache
                WHERE detaleCode != ?
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(1, $input['self']??'');
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>
            $v->sum,
        );
    }
}
