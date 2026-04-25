<?php
namespace saso\item;

use saso\entity;
use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\item;
use saso\repository\archive;
use saso\repository\Finder;
use saso\repository\TransactionInterface;
use saso\repository\Updater;
use saso\util\monad\Either;

final class ArchiveUsecase implements Usecase
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

        $archive = $data->id->flatMap(
            fn($v)=>$this->finder->current(new item\FindOneById(), ['id'=>$v])
        )->filter(
            fn($v)=>!$this->finder->current(new archive\FindOneByItem($v))->getOrElse(null)?->archive??false
        )->flatMap(
            fn($v)=>$data->note->flatMap(
            fn($n)=>new entity\Archive(
                $v,
                true,
                $n,
                $data->now,
            ))
        );
        $archive->flatMap(
            fn($v)=>$this->updater->exec(new archive\Archive($v))
        );

        $this->output = $archive->flatMap(
            function($v) {
                $this->transaction->commit();
                return 'item/start/item/'.$v->item->id;
            }
        )->orElse(
            function($v) {
                $this->transaction->rollBack();
                return Either::left('archive note is invalid or item is not found.');
            }
        );
    }
}