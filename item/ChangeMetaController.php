<?php
namespace saso\item;

use saso\entity;
use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\Getter;
use saso\util\monad\Either;

/**
 * @property Either<string> $note
 * @property Either<string> $janCode
 * @property Either<string> $isbnCode
 */
final class ChangeMetaController implements GettableController, DTO
{
    use Getter;
    private Either $note;
    private Either $janCode;
    private Either $isbnCode;
    public function __construct(
        array $post,
        private \DateTime $now,
    )
    {
        $this->note = entity\Item::noteConstraint(trim((string)($post['note']??'')));
        $this->janCode = entity\Item::janCodeConstraint(trim((string)($post['janCode']??'')));
        $this->isbnCode = entity\Item::isbnCodeConstraint(trim((string)($post['isbnCode']??'')));
    }
}
