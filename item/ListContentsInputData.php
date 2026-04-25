<?php
namespace saso\item;

use saso\framework\DTO;
use saso\framework\Getter;

final class ListContentsInputData implements DTO
{
    use Getter;
    public function __construct(
        private int $outputRow,
        private bool $isArchive,
        private int $page,
        private string $sortby,
        private string $direction,
        private string $search,
    )
    {
    }
}
