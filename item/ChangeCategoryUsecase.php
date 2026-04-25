<?php
namespace saso\item;

use saso\entity;
use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\archive;
use saso\repository\category;
use saso\repository\item;
use saso\repository\itemVar;
use saso\repository\Finder;
use saso\repository\TransactionInterface;
use saso\repository\Updater;
use saso\util\monad\Either;

final class ChangeCategoryUsecase implements Usecase
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

        $category = $data->categoryId->flatMap(
            fn($v)=>is_null($v)?$v:$this->finder->current(new category\FindOneById(), ['id'=>$v])
        );
        $itemVar = $data->id->flatMap(
            fn($v)=>$this->finder->current(new item\FindOneById(), ['id'=>$v])
        )->filter(
            fn($v)=>!$this->finder->current(new archive\FindOneByItem($v))->getOrElse(null)?->archive??false
        )->flatMap(
            fn($v)=>$category->flatMap(
            fn($c)=>new entity\ItemVar(
                $v,
                $c?->id,
                null,
                $data->now,
            )
        ));
        $itemVar->flatMap(
            fn($v)=>$this->updater->exec(new itemVar\ChangeCategory($v))
        );

        $this->output = $itemVar->flatMap(
            function($v) {
                $this->transaction->commit();
                return 'item/start/item/'.$v->item->id;
            }
        )->orElse(
            function($v) {
                $this->transaction->rollBack();
                return Either::left('item or category are not found.');
            }    
        );
    }
}