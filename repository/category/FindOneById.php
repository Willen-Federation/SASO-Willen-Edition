<?php
namespace saso\repository\category;

use saso\entity\Category;
use saso\repository\DbPrepare;
use saso\util\Each;

final class FindOneById implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            SELECT *
                FROM Category
                WHERE categoryId = ?
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(1, $input['id']);
    }
    public function map(): \Closure
    {
        return Each::tf(fn($i)=>new Category(
            $i->categoryId,
            $i->categoryName,
        ));
    }
}

