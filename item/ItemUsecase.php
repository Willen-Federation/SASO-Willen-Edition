<?php
namespace saso\item;

use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\archive;
use saso\repository\item\FindOneById;
use saso\repository\Finder;
use saso\util\monad\Either;

final class ItemUsecase implements Usecase
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
            fn($v)=>$this->finder->current(new FindOneById(), ['id'=>$v])
        )->filter(
            fn($v)=>!$this->finder->current(new archive\FindOneByItem($v))->getOrElse(null)?->archive??false
        );
    }
}
