<?php
namespace saso\repository\category;

use saso\entity\Category;
use saso\repository\DbPrepare;
use saso\util\Each;

final class FindOneParent implements DbPrepare
{
    public function __construct(
        private Category $self,
    )
    {
    }
    public function getQuery(): string
    {
        return '
            SELECT Parent.*
                FROM Category AS Child
                    INNER JOIN Category AS Parent
                    ON Parent.categoryLeft < Child.categoryLeft
                    AND Parent.categoryLeft = (SELECT MAX(categoryLeft)
                                              FROM Category
                                              WHERE Child.categoryLeft > categoryLeft
                                              AND Child.categoryLeft < categoryRight
                                             )
                WHERE Child.categoryId = ?
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(1, $this->self->id);
    }
    public function map(): \Closure
    {
        return Each::tf(fn($v)=>new Category(
            $v->categoryId,
            $v->categoryName,
        ));
    }
}
