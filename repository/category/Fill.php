<?php
namespace saso\repository\category;

use saso\repository\DbPrepare;

final class Fill implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            UPDATE Category
                SET categoryLeft = CASE WHEN categoryLeft < :deleteRight AND categoryLeft > :deleteLeft
                                        THEN categoryLeft -1
                                        WHEN categoryLeft > :deleteLeft
                                        THEN categoryLeft - 2 
                                        ELSE categoryLeft END,
                    categoryRight = CASE WHEN categoryRight > :deleteRight
                                         THEN categoryRight - 2
                                         WHEN categoryRight > :deleteLeft AND categoryRight < :deleteRight
                                         THEN categoryRight -1
                                         ELSE categoryRight END
                WHERE categoryLeft > :deleteLeft
                   OR categoryRight > :deleteRight
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':deleteLeft', $input['left']);
        $stmt->bindValue(':deleteRight', $input['right']);
    }
    public function map(): \Closure
    {
        return fn()=>null;
    }
}
