<?php
namespace saso\feature;

use saso\framework\DTO;
use saso\framework\Getter;
use saso\util\monad\Either;

final class HistoryOutput implements DTO
{
    use Getter;
    public function __construct(
        private Either $logs,
        private Either $archive,
    )
    {
    }
}