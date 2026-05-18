<?php
namespace saso\item;

use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\Getter;
use saso\util\monad\Either;

final class ChangeStatusController implements GettableController, DTO
{
    use Getter;

    public const VALID_STATUSES = [
        'active', 'archived', 'discontinued', 'pending',
        'in_storage', 'in_use', 'for_sale', 'reserved', 'shipped',
    ];

    private Either $status;

    public function __construct(
        array $post,
        private \DateTime $now,
    )
    {
        $value = $post['status'] ?? '';
        $this->status = Either::fromNullable(
            in_array($value, self::VALID_STATUSES, true) ? $value : null
        );
    }
}
