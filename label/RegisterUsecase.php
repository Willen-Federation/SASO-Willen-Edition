<?php
namespace saso\label;

use saso\entity\Label;
use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\label\FindOneByName;
use saso\repository\label\Insert;
use saso\repository\Finder;
use saso\repository\TransactionInterface;
use saso\repository\Updater;
use saso\util\monad\Either;

final class RegisterUsecase implements Usecase
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

        $this->output = $data->name->filter(
            fn($v)=>!$this->finder->current(new FindOneByName(), ['name'=>$v])->getOrElse(false)
        )->flatMap(
            fn($v)=>$data->marginTop->flatMap(
            fn($a)=>$data->marginLeft->flatMap(
            fn($b)=>$data->width->flatMap(
            fn($c)=>$data->height->flatMap(
            fn($d)=>$data->intervalColumn->flatMap(
            fn($e)=>$data->intervalRow->flatMap(
            fn($f)=>Label::createValidLabel($v, $a, $b, $c, $d, $e, $f)
        )))))))->flatMap(
            fn($v)=>$this->updater->exec(new Insert($v))
        )->flatMap(
            fn($v)=>$this->transaction->commit()
        )->flatMap(
            fn($v)=>'label/start'
        )->orElse(
            function($v) {
                $this->transaction->rollBack();
                return Either::left('length is invalid.');
            }
        );
    }
}