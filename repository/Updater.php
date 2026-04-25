<?php
namespace saso\repository;

interface Updater
{
    public function exec(DbPrepare $prepare, ?array $input=[]): void;
}