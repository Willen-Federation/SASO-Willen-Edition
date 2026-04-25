<?php
namespace saso\repository\shelf;

use saso\entity\Feature;
use saso\entity\Shelf;
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
                FROM Shelf
                WHERE detaleCode = ?
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(1, $this->feature->getFullCode());
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>new Shelf(
            $this->feature,
            $v->shelfNumber,
        ));
    }
}
