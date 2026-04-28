<?php
namespace saso\verify;

use saso\common\EmptyIO;
use saso\framework\Controller;
use saso\framework\DTO;
use saso\framework\Input;

final class StartController implements Controller
{
    use Input;
    private DTO $data;
    public function __construct()
    {
        $this->data = new EmptyIO();
    }
}
