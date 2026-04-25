<?php
namespace saso\item;

use saso\entity;
use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\archive;
use saso\repository\item;
use saso\repository\itemVar;
use saso\repository\Finder;
use saso\repository\TransactionInterface;
use saso\repository\Updater;
use saso\util\monad\Either;

final class ChangePriceUsecase implements Usecase
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

        $itemVar = $data->price->flatMap(
            fn($v)=>$data->id->flatMap(
                fn($i)=>$this->finder->current(new item\FindOneById(), ['id'=>$i])
            )->filter(
                fn($i)=>!$this->finder->current(new archive\FindOneByItem($i))->getOrElse(null)?->archive??false
            )->flatMap(
                fn($i)=>new entity\ItemVar(
                    $i,
                    null,
                    $v,
                    $data->now,
                )
            )
        );
        $itemVar->flatMap(
            fn($v)=>$this->updater->exec(new itemVar\ChangePrice($v))
        );

        $this->output = $itemVar->flatMap(
            function($v) {
                $this->transaction->commit();
                return 'item/start/item/'.$v->item->id;
            }
        )->orElse(
            function($v) {
                $this->transaction->rollBack();
                return Either::left('item is not found or invalid price.');
            }
        );
    }
}
