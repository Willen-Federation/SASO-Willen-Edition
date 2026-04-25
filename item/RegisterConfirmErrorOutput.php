<?php
namespace saso\item;

use saso\framework\DTO;
use saso\util\monad\Either;

final class RegisterConfirmErrorOutput implements DTO
{
    public function __construct(
        private Either $errorMessage,
    )
    {
    }
    public function __get($prop)
    {
        return $this->errorMessage;
    }
    public function __invoke()
    {
        return Either::left($this->errorMessage);
    }
}
