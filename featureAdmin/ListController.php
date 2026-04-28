<?php
namespace saso\featureAdmin;

use saso\common\EmptyIO;
use saso\framework\Controller;
use saso\framework\DTO;
use saso\framework\Input;

final class ListController implements Controller
{
    use Input;
    private DTO $data;
    public function __construct()
    {
        $this->data = new EmptyIO();
    }
}
