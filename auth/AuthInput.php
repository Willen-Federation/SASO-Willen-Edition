<?php
namespace saso\auth;

use saso\common\EmptyIO;
use saso\framework\DTO;
use saso\framework\GetterAndAnother;

final class AuthInput implements DTO
{
    use GetterAndAnother;
    public function __construct(
        private string $restoredPath,
        private bool $isError,
        private ?DTO $another=null,
    )
    {
        $this->another ??= new EmptyIO();
    }
}
