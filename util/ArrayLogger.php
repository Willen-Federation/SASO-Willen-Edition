<?php

namespace saso\util;

final class ArrayLogger implements Logger {
    private array $logArray = [];
    public function __construct(
    )
    {
    }
    public function log($val): void
    {
        $this->logArray[] = $val;
    }
    public function getLog(): array
    {
        return $this->logArray;
    }
}