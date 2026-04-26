<?php
namespace saso\auth;

use saso\framework\Controller;
use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\Input;

final class AuthController implements Controller
{
    use Input;
    private DTO $data;
    public function __construct(
        array $query,
        ?GettableController $anotherCtrl=null
    )
    {
        $restoredPath = preg_replace('/error\/1\//', '', $query['restoredPath']);
        $isError = preg_match('/error\/1\//', $query['restoredPath']) === 1;
        $this->data = new AuthInput(
            $restoredPath,
            $isError,
            $anotherCtrl,
        );
    }
}