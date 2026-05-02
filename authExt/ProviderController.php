<?php
namespace saso\authExt;

use saso\common\EmptyIO;
use saso\framework\Controller;
use saso\framework\DTO;
use saso\framework\Input;

final class ProviderController implements Controller
{
    use Input;
    private DTO $data;
    public function __construct(private array $query, private array $post)
    {
        $this->data = new EmptyIO();
    }
}
