<?php
namespace saso\category;

use saso\framework\DTO;
use saso\framework\GettableController;
use saso\framework\Getter;
use saso\util\monad\Either;

final class DeleteController implements GettableController, DTO
{
    use Getter;
    private Either $method;
    public function __construct(
        array $post,
        private \DateTime $now
    )
    {
        $this->method = Either::of($post['method']??'')->filter(
            fn($v)=>($v === 'childrenPromote' || $v === 'withChildren')
        );
    }
}