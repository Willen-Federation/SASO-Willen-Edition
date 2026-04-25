<?php
namespace saso\repository\label;

use saso\entity\Label;
use saso\repository\DbPrepare;

final class Insert implements DbPrepare
{
    private array $data;
    public function __construct(
        private Label $label,
    )
    {
    }
    public function getQuery(): string
    {
        return '
            INSERT INTO Label('
                .implode(',', array_keys($this->data)).
            ')
            VALUES ('
                .implode(',', array_map(fn($p)=>':'.$p, array_keys($this->data))).
            ')
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        foreach(array_keys($this->data) as $prop) {
            $stmt->bindValue(':'.$prop, $this->data[$prop]);
        }
    }
    public function map(): \Closure
    {
        $this->data = [
            'labelName'=>$this->label->name,
            'width'=>$this->label->width,
            'height'=>$this->label->height,
            'marginLeft'=>$this->label->marginLeft,
            'marginTop'=>$this->label->marginTop,
            'intervalColomn'=>$this->label->intervalColomn,
            'intervalRow'=>$this->label->intervalRow,
        ];
        return fn()=>$this->label;
    }
}
