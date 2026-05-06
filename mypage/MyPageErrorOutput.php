<?php

namespace saso\mypage;

use saso\common\EmptyIO;
use saso\framework\DTO;
use saso\framework\GetterAndAnother;

final class MyPageErrorOutput implements DTO
{
    use GetterAndAnother;

    private ?DTO $another;

    public function __construct(
        private readonly string $error,
    ) {
        $this->another = new EmptyIO();
    }
}
