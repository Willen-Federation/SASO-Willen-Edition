<?php
namespace saso\shelf;

use saso\framework\DTO;
use saso\framework\Getter;

final class MultiOutput implements DTO
{
    use Getter;
    public function __construct(
        private int $pagesAmount,
        private array $shelves,
        private int $page,
        private array $mins,
        private array $maxs,
        )
    {
    }
}

