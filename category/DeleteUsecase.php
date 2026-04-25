<?php
namespace saso\category;

use saso\entity\ItemVar;
use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\category\Delete;
use saso\repository\category\DeleteWithChildren;
use saso\repository\category\Fill;
use saso\repository\category\FillWithChildren;
use saso\repository\category\FindRangeById;
use saso\repository\Finder;
use saso\repository\itemVar\ChangeCategory;
use saso\repository\itemVar\FindByCategoryId;
use saso\repository\TransactionInterface;
use saso\repository\Updater;
use saso\util\Each;
use saso\util\monad\Either;

final class DeleteUsecase implements Usecase
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
            
            $deleting = $data->id
                ->flatMap(fn($v)=>$this->finder->current(new FindRangeById(), ['id'=>$v]))
                ->getOrElseThrow('Category is not found.');
            $paradicate = [
                'childrenPromote'=>[
                    'delete'=>new Delete(),
                    'fill'=>new Fill(),
                ],
                'withChildren'=>[
                    'delete'=>new DeleteWithChildren(),
                    'fill'=>new FillWithChildren(),
                ],
            ];
            $data->method->flatMap(
                fn($m)=>array_map(
                    fn($a)=>$this->updater->exec($paradicate[$m][$a], $deleting),
                    ['delete', 'fill']
                )
            )->orElse(fn($v)=>throw new \Exception('Method is invalid.'));
 
            $this->finder->generate(new FindByCategoryId(), ['categoryId'=>$deleting['id']])->flatMap(
                Each::tf(fn($v)=>new ItemVar(
                    $v->item,
                    null,
                    null,
                    $data->now,
                ))
            )->flatMap(
                Each::exec(fn($v)=>$this->updater->exec(new ChangeCategory($v)))
            );

            $this->transaction->commit();
        } catch (\Exception $e) {
            $this->transaction->rollBack();
            $this->output = Either::left($e->getMessage());
        }
    }
}