<?php
namespace saso\auth;

use saso\entity\Member;
use saso\framework\Controller;
use saso\framework\DirectInput;
use saso\framework\DTO;
use saso\framework\Getter;
use saso\util\monad\Either;

final class PasswordController implements Controller, DTO
{
    use DirectInput;
    use Getter;
    private Either $now;
    private Either $new;
    public function __construct(
        array $post,
    )
    {
        $this->now = Member::passwordConstraint($post['now']??'')->flatMap(
            fn($v)=>Member::hashed($v)
        );
        $this->new = Member::passwordConstraint($post['new']??'')->filter(
            fn($v)=>$v===$post['confirm']??''
        )->filter(
            fn($v)=>$v!==$post['now']??''
        )->flatMap(
            fn($v)=>Member::hashed($v)
        );
    }
}