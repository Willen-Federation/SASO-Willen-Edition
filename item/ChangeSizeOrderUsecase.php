<?php
namespace saso\item;

use saso\entity;
use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\archive;
use saso\repository\item;
use saso\repository\size;
use saso\repository\Finder;
use saso\repository\TransactionInterface;
use saso\repository\Updater;
use saso\util\Each;
use saso\util\monad\Either;

final class ChangeSizeOrderUsecase implements Usecase
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

        $item = $data->id->flatMap(
            fn($i)=>$this->finder->current(new item\FindOneById(), ['id'=>$i])
        )->filter(
            fn($v)=>!$this->finder->current(new archive\FindOneByItem($v))->getOrElse(null)?->archive??false
        );
        $item->flatMap(
            fn($i)=>$data->sizes->flatMap(
                Each::tf(fn($v)=>$v->flatMap(
                    fn($s)=>new entity\Size(
                        $i,
                        $s['code'],
                        '',
                        $s['orderNumber'],
                    )
                )
            ))
        )->flatMap(
            Each::exec(fn($v)=>$v->flatMap(
                fn($s)=>$this->updater->exec(new size\UpdateOrderNumber($s))
            ))
        );

        $this->output = $item->flatMap(
            function($v) {
                $this->transaction->commit();
                return 'item/start/item/'.$v->id;
            }
        )->orElse(
            function($v) {
                $this->transaction->rollBack();
                return Either::left('item is not found.');
            }
        );
    }
}