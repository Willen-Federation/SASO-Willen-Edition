<?php
namespace saso\repository\archive;

use saso\entity;
use saso\repository\DbPrepare;

final class Reproduction implements DbPrepare
{
    private array $data;
    public function __construct(
        private entity\Archive $archive,
    )
    {
    }
    public function getQuery(): string
    {
        return '
            UPDATE Item
                SET archive = 0
                  , archiveNote = NULL
                  , archiveAt = NULL
                WHERE concatId = :concatId
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
            'concatId'=>$this->archive->item->id,
        ];
        return fn()=>$this->archive;
    }
}
