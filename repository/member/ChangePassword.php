<?php
namespace saso\repository\member;

use saso\entity;
use saso\repository\DbPrepare;

final class ChangePassword implements DbPrepare
{
    private array $data;
    public function __construct(
        private entity\Member $member,
    )
    {
    }
    public function getQuery(): string
    {
        return '
            UPDATE Member
                SET password = :password
                WHERE id = :id
        ';
    }
    public function bind(\PDOStatement $stmt, array $input): void
    {
        foreach(array_keys($this->data) as $prop) {
            $stmt->bindValue(':'.$prop, $this->data[$prop]);
        }
    }
    public function map(): \Closure
    {
        $this->data = [
            'password'=>$this->member->password,
            'id'=>$this->member->id,
        ];
        return fn()=>$this->member;
    }
}
