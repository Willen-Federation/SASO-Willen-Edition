<?php
namespace saso\label;

use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\label\FindOneByName;
use saso\repository\label\Delete;
use saso\repository\Finder;
use saso\repository\TransactionInterface;
use saso\repository\Updater;
use saso\util\monad\Either;

final class DeleteUsecase implements Usecase
{
    use OutputForSingleEntity;
    private Either $output;
    public function __construct(
        private Finder $finder,
        private Updater $updater,
        private TransactionInterface $transaction,
        private Presenter $presenter,
    )
    {
    }
    public function handle(DTO $data): void
    {
        $this->transaction->begin();

        $this->output = $data->name->flatMap(
            fn($v)=>$this->finder->current(new FindOneByName(), ['name'=>$v])
        )->flatMap(
            fn($v)=>$this->updater->exec(new Delete($v))
        )->flatMap(
            fn($v)=>$this->transaction->commit()
        )->flatMap(
            fn($v)=>'label/start'
        )->orElse(
            function($v) {
                $this->transaction->rollBack();
                return Either::left('label not found.');
            }
        );
    }
}
