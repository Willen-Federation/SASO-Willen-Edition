<?php
namespace saso\repository;

final class DbUpdater implements Updater
{
    public function exec(DbPrepare $prepare, ?array $input=[]): void
    {
        $prepare->map();
        $stmt = DBConnection::pdo()->prepare($prepare->getQuery());
        $prepare->bind($stmt, $input);
        DBExecuter::write($stmt);
    }
}
