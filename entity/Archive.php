<?php

namespace saso\entity;

use saso\util\monad\Either;

final class Archive
{
    public function __construct(
        private Item $item,
        private bool $archive,
        private ?string $archiveNote,
        private ?\DateTime $archiveAt,
    )
    {
    }
    public function __get($name)
    {
        return $this->$name;
    }
    public static function archiveNoteConstraint(string $note): Either
    {
        return EntityConstraint::optionalNoteConstraint($note, 50);
    }
}
