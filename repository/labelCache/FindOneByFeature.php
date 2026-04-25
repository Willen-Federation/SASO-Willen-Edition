<?php
namespace saso\repository\labelCache;

use saso\entity\Feature;
use saso\entity\LabelCache;
use saso\repository\DbPrepare;
use saso\util\Each;

final class FindOneByFeature implements DbPrepare
{
    public function __construct(
        private Feature $feature,
    )
    {
    }
    public function getQuery(): string
    {
        return '
            SELECT *
                FROM LabelCache
                WHERE detaleCode = ?
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(1, $this->feature->getFullCode());
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>new LabelCache(
            $this->feature,
            $v->sheetsAmount,
        ));
    }
}
