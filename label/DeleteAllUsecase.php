<?php
namespace saso\label;

use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\labelCache\DeleteAll;
use saso\repository\Finder;
use saso\repository\TransactionInterface;
use saso\repository\Updater;
use saso\util\monad\Either;

final class DeleteAllUsecase implements Usecase
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

        $this->output = Either::of($this->updater->exec(new DeleteAll()))->flatMap(
            fn($v)=>$this->transaction->commit()
        )->flatMap(
            fn($v)=>'label/features'
        )->orElse(
            function($v) {
                $this->transaction->rollBack();
                return Either::left('unreachable error.');
            }
        );
    }
}