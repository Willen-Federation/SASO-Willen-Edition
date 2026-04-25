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
        // `now` stays as the raw user-supplied current password — the usecase
        // verifies it against the stored Argon2id hash via Member::verifyPassword.
        $this->now = Member::passwordConstraint($post['now']??'');

        // `new` is hashed here so the usecase only ever stores Argon2id digests.
        $this->new = Member::passwordConstraint($post['new']??'')->filter(
            fn($v)=>$v===($post['confirm']??'')
        )->filter(
            fn($v)=>$v!==($post['now']??'')
        )->flatMap(
            fn($v)=>Either::of(Member::hashPassword($v))
        );
    }
}
