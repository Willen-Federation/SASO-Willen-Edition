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
        $path = (string) ($query['restoredPath'] ?? '');
        // Reject absolute URLs (contains ://  or protocol-relative //) to prevent
        // credential phishing via a crafted restoredPath form action.
        if (preg_match('#(://|^//)#', $path)) {
            $path = '';
        }
        $restoredPath = preg_replace('/error\/1\//', '', $path);
        $isError = preg_match('/error\/1\//', $path) === 1;
        $this->data = new AuthInput(
            $restoredPath,
            $isError,
            $anotherCtrl,
        );
    }
}