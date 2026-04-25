<?php
namespace saso\repository\quantityLog;

use saso\entity\QuantityLogs;
use saso\repository\DbPrepare;

final class Delete implements DbPrepare
{
    public function __construct(
        private QuantityLogs $logs,
    )
    {
    }
    public function getQuery(): string
    {
        return '
            DELETE FROM QuantityLog
                WHERE detaleCode = ?
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(1, $this->logs->feature->getFullCode());
    }
    public function map(): \Closure
    {
        return fn()=>$this->logs;
    }
}
