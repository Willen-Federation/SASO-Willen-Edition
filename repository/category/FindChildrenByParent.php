<?php
namespace saso\repository\category;

use saso\entity\Category;
use saso\repository\DbPrepare;
use saso\util\Each;

final class FindChildrenByParent implements DbPrepare
{
    public function __construct(
        private Category $parent,
    )
    {
    }
    public function getQuery(): string
    {
        return '
            SELECT Child.categoryId, Child.categoryName
                FROM Category AS Parent
                    INNER JOIN Category AS Child
                    ON Parent.categoryLeft = (SELECT MAX(categoryLeft)
                                              FROM Category
                                              WHERE Child.categoryLeft > categoryLeft
                                              AND Child.categoryLeft < categoryRight
                                             )
                WHERE Parent.categoryId = ?
                ORDER BY Child.categoryName ASC
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(1, $this->parent->id);
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>new Category(
            $v->categoryId,
            $v->categoryName,
        ));
    }
}
