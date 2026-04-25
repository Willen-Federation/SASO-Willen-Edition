<?php
namespace saso\item;

use saso\entity;
use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\category;
use saso\repository\item;
use saso\repository\itemVar;
use saso\repository\color;
use saso\repository\size;
use saso\repository\Finder;
use saso\repository\TransactionInterface;
use saso\repository\Updater;
use saso\util\Each;
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
        try {
            $this->transaction->begin();

            $lastSerial = $this->finder->current(new item\FindLastSerialByDateCode(), [
                'now'=>entity\Item::makeDateCode($data->now)
            ])->getOrElse(0);
            $item = Either::of($lastSerial + 1)
            ->map(fn($v)=>new entity\Item(
                $v,
                $data->name->getOrElseThrow('Item name is invalid.'),
                $data->pla,
                $data->plaNote->getOrElseThrow('Item plaNote is invalid.'),
                $data->paper,
                $data->paperNote->getOrElseThrow('Item paperNote is invalid'),
                $data->now,
            ));
            $itemEither = $item->map(fn($v)=>new item\Insert($v))
            ->filter(fn($v)=>$v??false)
            ->map(Each::i())
            ->getOrElseThrow('Fail to insert item.');
            $category = $data->categoryId->flatMap(
                fn($v)=>$this->finder->current(new category\FindOneById(), ['id'=>$v])
            )->flatMap(
                fn($v)=>$v->id
            )->getOrElse(null);
            $itemVar = $item
            ->map(fn($v)=>new entity\ItemVar(
                $v,
                $category,
                $data->price->getOrElse(null),
                $v->createAt,
            ))
            ->map(fn($v)=>new itemVar\Insert($v))
            ->map(Each::i())
            ->getOrElseThrow('Fail to insert itemVar.');
            $colors = $data->colors
            ->map(Each::tf(fn($v)=>new entity\Color(
                $item->getOrElseThrow('Fail to create Item.'),
                $v->code->getOrElseThrow('Color code is invalid.'),
                $v->name->getOrElseThrow('Color name is invalid.'),
            )))
            ->map(Each::tf(fn($v)=>new color\Insert($v)))
            ->filter(fn($v)=>$v->valid())
            ->getOrElseThrow('Color is nothing');
            $sizes = $data->sizes
            ->map(Each::tf(fn($v)=>new entity\Size(
                $item->getOrElseThrow('Fail to create Item.'),
                $v->code->getOrElseThrow('Size code is invalid.'),
                $v->name->getOrElseThrow('Size name is invalid.'),
                $v->orderNumber->getOrElseThrow('Size orderNumber is invalid'),
            )))
            ->map(Each::tf(fn($v)=>new size\Insert($v)))
            ->filter(fn($v)=>$v->valid())
            ->getOrElseThrow('Size is nothing');
            Either::of(Each::t([$itemEither, $itemVar, $colors, $sizes]))
            ->map(Each::m())
            ->map(Each::exec(fn($v)=>$this->updater->exec($v)));

            if(!$data->validFeaturesAmount) {
                throw new \Exception('size or color is too many.');
            }

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
