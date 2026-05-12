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
        ?GettableController $anotherCtrl = null
    ) {
        $rp = (string) ($query['restoredPath'] ?? '');
        $restoredPath = preg_replace('/error\/1\//', '', $rp) ?? '';
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $restoredPath) === 1 || str_starts_with($restoredPath, '//')) {
            $restoredPath = '';
        }
        $isError = preg_match('/error\/1\//', $rp) === 1;
        $this->data = new AuthInput(
            $restoredPath,
            $isError,
            $anotherCtrl,
        );
    }
}
