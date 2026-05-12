<?php
namespace saso\item;

use saso\framework\DTO;
use saso\framework\Getter;

final class BulkImportInputData implements DTO
{
    use Getter;

    public function __construct(
        private array $rows,
        private \DateTime $now,
    ) {
    }
}
