<?php
namespace saso\category;

use saso\entity\Category;
use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\category\FindLastId;
use saso\repository\category\FindRangeById;
use saso\repository\category\Insert;
use saso\repository\category\NewRootsLeft;
use saso\repository\category\SecureChildsSpace;
use saso\repository\Finder;
use saso\repository\TransactionInterface;
use saso\repository\Updater;
use saso\util\monad\Either;

final class AddUsecase implements Usecase
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

            $left = $data->id->flatMap(
                fn($v)=>$this->finder->current(new FindRangeById(), ['id'=>$v])
            )->flatMap(
                function($v) {
                    $this->updater->exec(new SecureChildsSpace(), $v);
                    return $v['right'];
                }
            )->OrElse(
                fn($v)=>$this->finder->current(new NewRootsLeft())
            )->getOrElse(1);
            $this->finder->current(new FindLastId())->orElse(
                fn($v)=>Either::of(0)
            )->flatMap(
                fn($v)=>new Category(
                    $v+1,
                    $data->name->getOrElseThrow('name is required.')
                )
            )->flatMap(
                fn($v)=>$this->updater->exec(new Insert($v), [
                    'left'=>$left,
                    'right'=>$left+1,
                ])
            );

            $this->transaction->commit();
        } catch (\Exception $e) {
            $this->transaction->rollBack();
            $this->output = Either::left($e->getMessage());
        }
    }
}