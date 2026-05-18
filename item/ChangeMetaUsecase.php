<?php
namespace saso\item;

use saso\entity;
use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\archive;
use saso\repository\item;
use saso\repository\Finder;
use saso\repository\TransactionInterface;
use saso\repository\Updater;
use saso\util\monad\Either;

final class ChangeMetaUsecase implements Usecase
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

        $existing = $data->id->flatMap(
            fn($i)=>$this->finder->current(new item\FindOneById(), ['id'=>$i])
        )->filter(
            fn($i)=>!$this->finder->current(new archive\FindOneByItem($i))->getOrElse(null)?->archive??false
        );

        $updated = $existing->flatMap(
            fn($i)=>new entity\Item(
                $i->serial,
                $i->name,
                $i->pla,
                $i->plaNote,
                $i->paper,
                $i->paperNote,
                $i->createAt,
                $i->status,
                $data->note->getOrElse(null) ?: null,
                $data->janCode->getOrElse(null) ?: null,
                $data->isbnCode->getOrElse(null) ?: null,
            )
        );

        $updated->flatMap(
            fn($v)=>$this->updater->exec(new item\ChangeMeta($v))
        );

        $this->output = $updated->flatMap(
            function($v) {
                $this->transaction->commit();
                return 'item/start/item/'.$v->id;
            }
        )->orElse(
            function($v) {
                $this->transaction->rollBack();
                return Either::left('item is not found or invalid meta.');
            }
        );
    }
}
