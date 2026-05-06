<?php
namespace saso\authExt;

use saso\common\EmptyIO;
use saso\framework\Controller;
use saso\framework\DTO;
use saso\framework\Input;

/**
 * No-op controller for the admin Auth Providers list. The view fetches
 * data on its own from the DB — there is no per-request input to compile.
 */
final class ProvidersListController implements Controller
{
    use Input;
    private DTO $data;
    public function __construct()
    {
        $this->data = new EmptyIO();
    }
}
