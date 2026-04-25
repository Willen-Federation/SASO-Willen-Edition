<?php
namespace saso\repository\archive;

use saso\entity;
use saso\repository\DbPrepare;

final class Archive implements DbPrepare
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
                SET archive = 1
                  , archiveNote = :archiveNote
                  , archiveAt = :archiveAt
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
            'archiveNote'=>$this->archive->archiveNote,
            'archiveAt'=>$this->archive->archiveAt->format('Y-m-d H:i:s'),
            'concatId'=>$this->archive->item->id,
        ];
        return fn()=>$this->archive;
    }
}
