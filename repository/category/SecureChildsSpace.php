<?php
namespace saso\repository\category;

use saso\repository\DbPrepare;

final class SecureChildsSpace implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            UPDATE Category
                SET categoryLeft = CASE WHEN categoryLeft > :parentRight
                                        THEN categoryLeft + 2
                                        ELSE categoryLeft END,
                    categoryRight = CASE WHEN categoryRight >= :parentRight
                                        THEN categoryRight + 2
                                        ELSE categoryRight END
            WHERE categoryRight >= :parentRight
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':parentRight', $input['right']);
    }
    public function map(): \Closure
    {
        return fn()=>null;
    }
}

