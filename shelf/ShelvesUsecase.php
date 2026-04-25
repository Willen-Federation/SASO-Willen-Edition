<?php
namespace saso\shelf;

use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\util\monad\Either;

final class ShelvesUsecase implements Usecase
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
        $this->output = $data->shelves->orElse(
            fn($v)=>Either::left('invalid single shelf input.')
        );
    }
}
