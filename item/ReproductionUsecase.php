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

final class ReproductionUsecase implements Usecase
{
    use OutputForSingleEntity;
    private Either $output;
    public function __construct(
        private Finder $finder,
        private Updater $updater,
        private TransactionInterface $transaction,
        private Presenter $presenter
    )
    {
    }
    public function handle(DTO $data): void
    {
        try {
            $this->transaction->begin();

            $item = $data->id->flatMap(
                fn($v)=>$this->finder->current(new item\FindOneById(), ['id'=>$v])
            );
            $item->flatMap(
                fn($v)=>$this->finder->current(new archive\FindOneByItem($v))
            )->filter(
                fn($v)=>$v->archive
            )->flatMap(
                fn($v)=>new entity\Archive(
                    $v->item,
                    false,
                    null,
                    null,
                )
            )->flatMap(
                fn($v)=>$this->updater->exec(new archive\Reproduction($v))
            )->getOrElseThrow('fail to reproduction.');

            $this->transaction->commit();
            $this->output = $item->flatMap(
                fn($v)=>'item/start/item/'.$v->id
            );
        } catch (\Exception $e) {
            $this->transaction->rollBack();
            $this->output = Either::left($e->getMessage());
        }
    }
}
