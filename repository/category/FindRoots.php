<?php
namespace saso\repository\category;

use saso\entity\Category;
use saso\repository\DbPrepare;
use saso\util\Each;

final class FindRoots implements DbPrepare
{
    public function getQuery(): string
    {
        return '
            SELECT categoryId, categoryName
            FROM (
            SELECT Child.categoryId, Child.categoryName, COUNT(Parent.categoryId) AS level
                FROM Category AS Child, Category AS Parent
                WHERE Child.categoryLeft BETWEEN Parent.categoryLeft AND Parent.categoryRight
                GROUP BY Child.categoryId
            ) AS LevelTable
            WHERE level = 1
            ORDER BY categoryName ASC
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>new Category(
            $v->categoryId,
            $v->categoryName,
        ));
    }
}
