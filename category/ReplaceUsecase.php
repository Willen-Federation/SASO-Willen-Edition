<?php
namespace saso\category;

use saso\entity\Category;
use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\category\FindOneById;
use saso\repository\category\Update;
use saso\repository\Finder;
use saso\repository\TransactionInterface;
use saso\repository\Updater;
use saso\util\monad\Either;

final class ReplaceUsecase implements Usecase
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
        $this->output = Either::of(true);
    }
    public function handle(DTO $data): void
    {
        try {
            $this->transaction->begin();

            $data->id->flatMap(
                fn($v)=>$this->finder->current(new FindOneById(), ['id'=>$v])
            )->flatMap(
                fn($v)=>new Category(
                    $v->id,
                    $data->name->getOrElseThrow('name is required.')
                )
            )->flatMap(
                fn($v)=>$this->updater->exec(new Update($v))
            )->getOrElseThrow('category is not found.');

            $this->transaction->commit();
        } catch (\Exception $e) {
            $this->transaction->rollBack();
            $this->output = Either::left($e->getMessage());
        }
    }
}
