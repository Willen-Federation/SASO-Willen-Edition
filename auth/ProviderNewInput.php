<?php

namespace saso\auth;

use saso\common\EmptyIO;
use saso\framework\DTO;
use saso\framework\GetterAndAnother;

final class ProviderNewInput implements DTO
{
    use GetterAndAnother;

    public function __construct(
        private string $errorMessage = '',
        private ?DTO $another = null,
    ) {
        $this->another ??= new EmptyIO();
    }
}
