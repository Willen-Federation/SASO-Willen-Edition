<?php
namespace saso\repository;

use saso\util\Each;
use saso\util\monad\Either;

interface Finder
{
    /** @return Either<Each<mixed>> */
    public function generate(DbPrepare $prepare, ?array $input=[]): Either;
    /** @return Either<mixed> */
    public function current(DbPrepare $prepare, ?array $input=[]): Either;
}