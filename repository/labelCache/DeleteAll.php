<?php
namespace saso\repository\labelCache;

use saso\repository\DbPrepare;

final class DeleteAll implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            DELETE FROM LabelCache
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
    }
    public function map(): \Closure
    {
        return fn()=>null;
    }
}
