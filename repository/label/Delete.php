<?php
namespace saso\repository\label;

use saso\entity\Label;
use saso\repository\DbPrepare;

final class Delete implements DbPrepare
{
    public function __construct(
        private Label $label,
    )
    {
    }
    public function getQuery(): string
    {
        return '
            DELETE FROM Label
                WHERE labelName = ?
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(1, $this->label->name);
    }
    public function map(): \Closure
    {
        return fn()=>$this->label;
    }
}
