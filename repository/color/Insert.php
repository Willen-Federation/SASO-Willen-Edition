<?php
namespace saso\repository\color;

use saso\entity\Color;
use saso\repository\DbPrepare;

final class Insert implements DbPrepare
{
    private array $data;
    public function __construct(
        private Color $color,
    )
    {
    }
    public function getQuery(): string
    {
        return '
            INSERT INTO Color('
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
            'concatId'=>$this->color->item->id,
            'colorCode'=>$this->color->code,
            'colorName'=>$this->color->name,
            'image'=>'null',
            'imageType'=>'null',
        ];
        return fn()=>$this->color;
    }
}
