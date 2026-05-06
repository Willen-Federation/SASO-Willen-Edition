<?php
namespace saso\installer;

use saso\entity\Member;
use saso\framework\Controller;
use saso\framework\DirectInput;
use saso\framework\DTO;
use saso\framework\Getter;
use saso\util\monad\Either;

final class InstallController implements Controller, DTO
{
    use DirectInput;
    use Getter;
    private Either $id;
    private Either $name;
    private Either $password;
    public function __construct(
        array $post,
    )
    {
        $this->id = Either::fromNullable(filter_var(
            $post['id']??'',
            \FILTER_VALIDATE_REGEXP,
            [
                'options'=>[
                    'default'=>false,
                    'regexp'=>'/^[0-9a-zA-Z-_]{8,20}$/'
                ]
            ]
        ));
        $this->name = Member::nameConstraint($post['name']??'');
        $this->password = Either::fromNullable(filter_var(
            $post['password']??'',
            \FILTER_VALIDATE_REGEXP,
            [
                'options'=>[
                    'default'=>false,
                    'regexp'=>'/^[0-9a-zA-Z]{8,20}$/'
                ]
            ]
        ))->filter(
            fn($v)=>$v===$post['password_confirm']
        );
    }
}