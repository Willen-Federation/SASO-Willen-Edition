<?php
namespace saso\item;

use saso\framework\DTO;
use saso\framework\Getter;

final class PaginationOutput implements DTO
{
    use Getter;
    public function __construct(
        private int $pageAmount,
        private string $sortby,
        private string $direction,
        private string $search,
        private string $page,
    )
    {
    }
}
