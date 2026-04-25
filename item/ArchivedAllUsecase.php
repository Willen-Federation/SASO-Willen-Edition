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
use saso\util\Each;
use saso\util\monad\Either;

final class ArchivedAllUsecase implements Usecase
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
        try {
            $this->transaction->begin();

            $data->ids->flatMap(
                Each::tf(fn($v)=>$this->finder->current(new item\FindOneById(), ['id'=>$v->getOrElseThrow('item id is invalid.')]))
            )->flatMap(
                Each::tf(fn($v)=>$v->filter(
                    fn($i)=>!$this->finder->current(new archive\FindOneByItem($i))->getOrElse(null)?->archive??false
                )->getOrElseThrow('some item has archived.'))
            )->flatMap(
                Each::tf(fn($v)=>$data->note->flatMap(
                    fn($n)=>new entity\Archive(
                        $v,
                        true,
                        $n,
                        $data->now,
                    )
                )->getOrElseThrow('archive note is invalid.'))
            )->flatMap(
                Each::exec(fn($v)=>$this->updater->exec(new archive\Archive($v)))
            );
            $this->transaction->commit();
            $this->output = Either::of('item/archivingAll');
        } catch (\Exception $e) {
            $this->transaction->rollBack();
            $this->output = Either::left($e->getMessage());
        }
    }
}