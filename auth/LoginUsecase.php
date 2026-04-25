<?php
namespace saso\auth;

use saso\entity\Member;
use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\member\FindOneByAuth;
use saso\repository\Finder;
use saso\util\monad\Either;

final class LoginUsecase implements Usecase
{
    use OutputForSingleEntity;
    private Either $output;
    public function __construct(
        private Finder $finder,
        private Presenter $presenter,
    )
    {
    }
    public function handle(DTO $data): void
    {
        $this->output = $data->id->flatMap(
            fn($i)=>$data->password->flatMap(
                fn($p)=>$this->finder->current(new FindOneByAuth(), [
                    'id'=>$i,
                    'password'=>Member::hashed($p),
                ])
            )
        )->flatMap(function($v) use ($data){
            $_SESSION['id'] = $v->id;
            $_SESSION['time'] = time();
            $_SESSION['userName'] = $v->name;
            return $data->restoredPath;
        })->OrElse(fn($v)=>Either::left($data->restoredPath.'error/1/'));
    }
}
