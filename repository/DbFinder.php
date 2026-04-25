<?php
namespace saso\repository;

use saso\util\monad\Either;

final class DbFinder implements Finder
{
    /** @return Either<Generator<mixed>> */
    public function generate(DbPrepare $prepare, ?array $input=[]): Either
    {
        $stmt = DBConnection::pdo()->prepare($prepare->getQuery());
        $prepare->bind($stmt, $input);
        return Either::of(DBExecuter::read($stmt))
            ->flatMap($prepare->map());
    }
    /** @return Either<mixed> */
    public function current(DbPrepare $prepare, ?array $input=[]): Either
    {
        return $this->generate($prepare, $input)
            ->flatMap(fn($v)=>$v->current())
            ->filter(fn($v)=>$v);
    }
}
