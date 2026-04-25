<?php
namespace saso\framework;

use saso\util\monad\Either;

trait Output
{
    public function output(): View
    {
        return $this->presenter->complete(Either::of($this->output));
    }
}
