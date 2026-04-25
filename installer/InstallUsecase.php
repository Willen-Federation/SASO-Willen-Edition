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
        $this->output = $data->id->flatMap(
            fn($v)=>$data->name->flatMap(
            fn($n)=>$data->password->flatMap(
            fn($p)=>new Member($v, $n, $p)
        )));
    }
}