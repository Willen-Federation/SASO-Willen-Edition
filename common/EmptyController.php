<?php
namespace saso\common;

use saso\framework\Controller;
use saso\framework\DTO;
use saso\framework\Input;

final class EmptyController implements Controller
{
    use Input;
    private DTO $data;
    public function __construct(
    )
    {
        $this->data = new EmptyIO();
    }
}

