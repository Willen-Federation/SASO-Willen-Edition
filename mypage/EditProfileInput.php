<?php

namespace saso\mypage;

use saso\common\EmptyIO;
use saso\framework\DTO;
use saso\framework\GetterAndAnother;

final class EditProfileInput implements DTO
{
    use GetterAndAnother;

    private ?DTO $another;

    public function __construct(
        private readonly ?string $displayName,
        private readonly ?string $bio,
        private readonly ?string $avatarUrl,
    ) {
        $this->another = new EmptyIO();
    }
}
