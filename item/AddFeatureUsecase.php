<?php
namespace saso\item;

use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\archive;
use saso\repository\color;
use saso\repository\size;
use saso\repository\Finder;
use saso\repository\item\FindOneById;
use saso\repository\TransactionInterface;
use saso\repository\Updater;
use saso\util\monad\Either;

final class AddFeatureUsecase implements Usecase
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
        $item = $data->id->flatMap(
            fn($v)=>$this->finder->current(new FindOneById(), ['id'=>$v])
        )->filter(
            fn($v)=>!$this->finder->current(new archive\FindOneByItem($v))->getOrElse(null)?->archive??false
        );
        $oldColors = $item->flatMap(
            fn($v)=>$this->finder->generate(new color\FindByItem($v))
        );
        $oldSizes = $item->flatMap(
            fn($v)=>$this->finder->generate(new size\FindByItem($v))
        );
        $features = AddFeatureHelper::output(
            $item,
            $oldColors->flatMap(fn($v)=>iterator_to_array($v)),
            $oldSizes->flatMap(fn($v)=>iterator_to_array($v)),
            $data->colors,
            $data->sizes,
        );
        try{
            $this->transaction->begin();

            $this->output = $features->item->flatMap(
                fn($v)=>'item/start/item/'.$v->id
            )->orElse(fn($v)=>throw new \Exception('item is not found.'));
            if(!$features->isValidAmount) {
                throw new \Exception('size or color is too many.');
            }
            $features->colors->flatMap(
                fn($e)=>array_map(
                    fn($c)=>$this->updater->exec(new color\Insert($c)),
                    $e->getOrElseThrow('color is invalid.')
                )
            );
            $features->sizes->flatMap(
                fn($e)=>array_map(
                    fn($s)=>$this->updater->exec(new size\Insert($s)),
                    $e->getOrElseThrow('size is invalid.')
                )
            );

            $this->transaction->commit();
        } catch(\Exception $e) {
            $this->transaction->rollBack();
            $this->output = Either::left($e->getMessage());
        }
    }
}
