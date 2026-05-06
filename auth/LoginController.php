<?php
namespace saso\auth;

use saso\entity\Member;
use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\Getter;
use saso\util\monad\Either;

final class LoginController implements GettableController, DTO
{
    use Getter;
    private Either $id;
    private Either $password;
    public function __construct(
        array $post,
    )
    {
        $this->id = Member::idConstraint($post['id']??'');
        $this->password = Member::loginPasswordConstraint($post['password']??'');
    }
}
