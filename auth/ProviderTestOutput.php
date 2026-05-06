<?php

namespace saso\auth;

use saso\common\EmptyIO;
use saso\framework\DTO;
use saso\framework\GetterAndAnother;

final class ProviderTestOutput implements DTO
{
    use GetterAndAnother;

    private ?DTO $another;

    public function __construct(
        private readonly bool $ok = false,
        private readonly string $message = '',
        private readonly ?array $details = null,
    ) {
        $this->another = new EmptyIO();
    }
}
