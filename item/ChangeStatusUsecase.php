<?php
namespace saso\item;

use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\item;
use saso\repository\Finder;
use saso\repository\TransactionInterface;
use saso\repository\Updater;
use saso\util\monad\Either;

final class ChangeStatusUsecase implements Usecase
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

        $item = $data->status->flatMap(
            fn($s)=>$data->id->flatMap(
                fn($i)=>$this->finder->current(new item\FindOneById(), ['id'=>$i])
            )->flatMap(
                fn($i)=>$this->updater->exec(new item\ChangeStatus($i, $s, $data->now))
            )
        );

        $this->output = $item->flatMap(
            function($v) {
                $this->transaction->commit();
                return 'item/start/item/'.$v->id;
            }
        )->orElse(
            function($v) {
                $this->transaction->rollBack();
                return Either::left('item is not found or invalid status.');
            }
        );
    }
}
