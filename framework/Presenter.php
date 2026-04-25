<?php
namespace saso\framework;

use saso\util\monad\Either;

interface Presenter
{
    public function complete(Either $output): View;
}
