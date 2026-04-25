<?php

namespace saso\util;

interface Logger {
    public function log($val): void;
    public function getLog(): array;
}