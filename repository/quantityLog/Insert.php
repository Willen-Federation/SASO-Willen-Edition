<?php
namespace saso\repository\quantityLog;

use saso\entity\QuantityLog;
use saso\entity\QuantityLogs;
use saso\repository\DbPrepare;

final class Insert implements DbPrepare
{
    private array $data;
    public function __construct(
        private QuantityLog $log,
        private QuantityLogs $logs,
    )
    {
    }
    public function getQuery(): string
    {
        return '
            INSERT INTO QuantityLog('
                .implode(',', array_keys($this->data)).
            ')
            VALUES ('
                .implode(',', array_map(fn($p)=>':'.$p, array_keys($this->data))).
            ')
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        foreach(array_keys($this->data) as $prop) {
            $stmt->bindValue(':'.$prop, $this->data[$prop]);
        }
    }
    public function map(): \Closure
    {
        $this->data = [
            'detaleCode'=>$this->logs->feature->getFullCode(),
            'fluctuation'=>$this->log->fluctuation,
            'inventoryFlag'=>$this->log->isInventory?1:0,
            'changeAt'=>$this->log->changeAt->format('Y-m-d H:i:s'),
        ];
        return fn()=>$this->log;
    }
}
