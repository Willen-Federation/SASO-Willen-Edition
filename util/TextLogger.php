<?php

namespace saso\util;

final class TextLogger implements Logger {
    private string $fileName;
    public function __construct(
        array $config,
        \DateTime $dt,
    )
    {
        $this->fileName = $config['logPath'].'log_'.$dt->format('Y-m-d_H-i').'.txt';
    }
    public function log($val): void
    {
        error_log("\n".var_export($val, true)."\n", 3, $this->fileName);
    }
    public function getLog(): array 
    {
        return [];
    }
}