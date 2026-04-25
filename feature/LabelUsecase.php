<?php
namespace saso\feature;

use saso\entity\LabelCache;
use saso\entity\LabelSheets;
use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\feature\FindOneByFullcode;
use saso\repository\Finder;
use saso\repository\TransactionInterface;
use saso\repository\Updater;
use saso\repository\labelCache\FindOneByFeature;
use saso\repository\labelCache\Insert;
use saso\repository\labelCache\SumAll;
use saso\repository\labelCache\Update;
use saso\util\monad\Either;

final class LabelUsecase implements Usecase
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

        $labelSheets = new LabelSheets($data->sheetsMax);
        $this->output = $data->id->flatMap(
            fn($i)=>$data->color->flatMap(
            fn($c)=>$data->size->flatMap(
            fn($s)=>$this->finder->current(new FindOneByFullcode($this->finder), [
                'item'=>$i,
                'color'=>$c,
                'size'=>$s,
        ]))))->flatMap(
            fn($v)=>$data->amount->flatMap(
            fn($a)=>new LabelCache($v, $a)
        ))->filter(
            fn($v)=>$labelSheets->addable(
                $v,
                fn($feature)=>$this->finder->current(new SumAll(), [
                    'self'=>$feature->getFullCode()
                ])->getOrElse(0)
            )
        )->flatMap(
            function($v) {
                if($this->finder->current(new FindOneByFeature($v->feature))->getOrElse(false)) {
                    $this->updater->exec(new Update($v));
                } else {
                    $this->updater->exec(new Insert($v));
                }
                return $v;
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
                .'/action/label'
        )->orElse(
            function($v) {
                $this->transaction->rollBack();
                return Either::left('invalid input.');
            }
        );
    }
}