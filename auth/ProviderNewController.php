<?php

namespace saso\auth;

use saso\framework\Controller;
use saso\framework\Input;
use saso\framework\Usecase;

final class ProviderNewController implements Controller
{
    use Input;

    private ProviderNewInput $data;

    public function __construct(array $query)
    {
        $this->data = new ProviderNewInput();
    }
}
