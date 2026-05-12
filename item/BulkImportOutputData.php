<?php
namespace saso\item;

use saso\framework\DTO;
use saso\framework\Getter;

final class BulkImportOutputData implements DTO
{
    use Getter;

    public function __construct(
        private array $results,
        private int $successCount,
        private int $errorCount,
    ) {
    }
}
