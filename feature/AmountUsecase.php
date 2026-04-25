<?php
namespace saso\feature;

use saso\entity\QuantityLog;
use saso\entity\QuantityLogs;
use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\archive\FindOneByItem;
use saso\repository\feature\FindOneByFullcode;
use saso\repository\quantityLog\FindByFeature;
use saso\repository\quantityLog\Insert;
use saso\repository\quantityLog\Delete;
use saso\repository\Finder;
use saso\repository\TransactionInterface;
use saso\repository\Updater;
use saso\util\monad\Either;

final class AmountUsecase implements Usecase
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

        $this->output = $data->id->flatMap(
            fn($i)=>$data->color->flatMap(
            fn($c)=>$data->size->flatMap(
            fn($s)=>$this->finder->current(new FindOneByFullcode($this->finder), [
                'item'=>$i,
                'color'=>$c,
                'size'=>$s,
            ])))
        )->filter(
            fn($v)=>!$this->finder->current(new FindOneByItem($v->item))->getOrElse(null)?->archive??false
        )->flatMap(
            fn($v)=>new QuantityLogs(
                $v,
                $this->finder->generate(new FindByFeature($v))
            )
        )->flatMap(
            fn($v)=>$data->amount->flatMap(
                fn($a)=>$data->kind->flatMap(
                fn($k)=>[
                    'logs'=>$v,
                    'adding'=>QuantityLog::fromFramework($a, $k, $data->now)
                ]
            ))
        )->filter(
            fn($v)=>$v['logs']->addable($v['adding'])
        )->flatMap(
            function($v) {
                if($v['adding']->isInventory) {
                    $this->updater->exec(new Delete($v['logs']));
                }
                $this->updater->exec(new Insert($v['adding'], $v['logs']));
                return $v['logs'];
            }
        )->flatMap(
            function($v) {
                $this->transaction->commit();
                return $v;
            }
        )->flatMap(
            fn($v)=>'item/start/item/'.$v->feature->item->id
                .'/color/'.$v->feature->color->code
                .'/size/'.$v->feature->size->code
                .'/action/'.$data->kind->getOrElse('')
        )->orElse(
            function($v) {
                $this->transaction->rollBack();
                return Either::left('invalid input.');
            }
        );
    }
}
