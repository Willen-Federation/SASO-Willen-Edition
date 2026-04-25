<?php
namespace saso\repository\quantityLog;

use saso\entity\QuantityLog;
use saso\entity\Feature;
use saso\repository\DbPrepare;
use saso\util\Each;

final class FindByFeature implements DbPrepare
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
                FROM QuantityLog
                WHERE detaleCode = ?
                ORDER BY changeAt
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(1, $this->feature->getFullCode());
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>QuantityLog::fromRepository(
            $v->fluctuation,
            $v->inventoryFlag==='1',
            new \DateTime($v->changeAt),
        ));
    }
}
