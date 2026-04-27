<?php
namespace saso\installer;

use saso\entity\Member;
use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\util\monad\Either;

final class InstallUsecase implements Usecase
{
    use OutputForSingleEntity;
    private Either $output;
    public function __construct(
        private Presenter $presenter,
    )
    {
    }
    public function handle(DTO $data): void
    {
        $this->output = $data->dbHost->flatMap(
            fn($h)=>$data->dbPort->flatMap(
            fn($pt)=>$data->dbName->flatMap(
            fn($dn)=>$data->dbUser->flatMap(
            fn($du)=>$data->dbPassword->flatMap(
            fn($dp)=>$data->dbCharset->flatMap(
            fn($dc)=>$data->httpsEnabled->flatMap(
            fn($hs)=>$data->id->flatMap(
            fn($v)=>$data->name->flatMap(
            fn($n)=>$data->password->flatMap(
            fn($p)=>new Member($v, $n, $p)
        ))))))))));
    }
}
