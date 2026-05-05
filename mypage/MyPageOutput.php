<?php

namespace saso\mypage;

use saso\common\EmptyIO;
use saso\entity\Member;
use saso\framework\DTO;
use saso\framework\GetterAndAnother;

final class MyPageOutput implements DTO
{
    use GetterAndAnother;

    private ?DTO $another;

    public function __construct(
        private readonly Member $member,
    ) {
        $this->another = new EmptyIO();
    }
}
