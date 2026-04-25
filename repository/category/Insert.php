<?php
namespace saso\repository\category;

use saso\entity\Category;
use saso\repository\DbPrepare;

final class Insert implements DbPrepare
{
    private array $data;
    public function __construct(
        private Category $category,
    )
    {
    }
    public function getQuery(): string
    {
        return '
            INSERT INTO Category('
                .implode(',', array_keys($this->data)).
            ')
            VALUES ('
                .implode(',', array_map(fn($p)=>':'.$p, array_keys($this->data))).
            ')
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        $this->data['categoryLeft'] = $input['left'];
        $this->data['categoryRight'] = $input['right'];
        foreach(array_keys($this->data) as $prop) {
            $stmt->bindValue(':'.$prop, $this->data[$prop]);
        }
    }
    public function map(): \Closure
    {
        $this->data = [
            'categoryId'=>$this->category->id,
            'categoryName'=>$this->category->name,
            'categoryLeft'=>null,
            'categoryRight'=>null,
        ];
        return fn()=>$this->category;
    }
}

