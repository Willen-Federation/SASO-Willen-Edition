<?php

namespace saso\auth;

use saso\common\EmptyIO;
use saso\framework\DTO;
use saso\framework\GetterAndAnother;

final class AuthOutput implements DTO
{
    use GetterAndAnother;

    private ?DTO $another;

    public function __construct(
        private readonly string $restoredPath,
        private readonly bool $isError,
        private readonly array $providers,
    ) {
        $this->another = new EmptyIO();
    }
}
