<?php
namespace saso\repository\category;

use saso\entity\Category;
use saso\repository\DbPrepare;

final class Update implements DbPrepare
{
    public function __construct(
        private Category $category,
    )
    {
    }
    public function getQuery(): string
    {
        return '
            UPDATE Category
                SET categoryName = :categoryName
            WHERE categoryId = :categoryId
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $stmt->bindValue(':categoryName', $this->category->name);
        $stmt->bindValue(':categoryId', $this->category->id);
    }
    public function map(): \Closure
    {
        return fn()=>$this->category;
    }
}
