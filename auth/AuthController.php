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
        // Errors arrive in one of two shapes:
        //  - legacy: `error/1/` segment baked into the restoredPath (e.g. when the
        //    LoginUsecase appended it to an in-flight protected path), or
        //  - direct: `error=1` query (e.g. `/auth/start/error/1/` after a failed
        //    POST from an embedded webview that had no restoredPath to anchor on).
        // Either form is sufficient to render the error banner.
        $isError = preg_match('/error\/1\//', $rp) === 1
            || (string) ($query['error'] ?? '') === '1';
        $this->data = new AuthInput(
            $restoredPath,
            $isError,
            $anotherCtrl,
        );
    }
}
