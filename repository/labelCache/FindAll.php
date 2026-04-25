<?php
namespace saso\repository\labelCache;

use saso\repository\DbPrepare;
use saso\util\Each;

final class FindAll implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            SELECT *
                FROM LabelCache
                WHERE sheetsAmount > 0
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>[
            'fullCode'=>$v->detaleCode,
            'amount'=>$v->sheetsAmount,
        ]);
    }
}
