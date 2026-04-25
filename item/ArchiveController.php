<?php
namespace saso\item;

use saso\entity;
use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\Getter;
use saso\util\monad\Either;

final class ArchiveController implements GettableController, DTO
{
    use Getter;
    private Either $note;
    public function __construct(
        array $post,
        private \DateTime $now,
    )
    {
        $this->note = entity\Archive::archiveNoteConstraint($post['archiveNote']??'');
    }
}