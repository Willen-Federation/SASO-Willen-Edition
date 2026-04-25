<?php
namespace saso\repository\color;

use saso\entity\Color;
use saso\repository\DbPrepare;

final class UploadImage implements DbPrepare
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
            UPDATE Color
                SET image = :image
                  , imageType = :imageType
                WHERE concatId = :concatId AND colorCode = :colorCode
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        foreach(array_keys($this->data) as $prop) {
            $stmt->bindValue(':'.$prop, $this->data[$prop]);
        }
        $f = fopen($input['fileName'], 'rb');
        $stmt->bindValue(':image', fread($f, filesize($input['fileName'])));
        fclose($f);
        $stmt->bindValue(':imageType', $input['imageType']);
    }
    public function map(): \Closure
    {
        $this->data = [
            'concatId'=>$this->color->item->id,
            'colorCode'=>$this->color->code,
        ];
        return fn()=>$this->color;
    }
}
