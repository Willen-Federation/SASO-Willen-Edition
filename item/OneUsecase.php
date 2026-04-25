<?php
namespace saso\item;

use saso\entity\Feature;
use saso\entity\QuantityLogs;
use saso\framework\DTO;
use saso\framework\Output;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\archive;
use saso\repository\color;
use saso\repository\size;
use saso\repository\quantityLog;
use saso\repository\shelf;
use saso\repository\labelCache;
use saso\repository\item\FindOneById;
use saso\repository\labelCache\SumAll;
use saso\repository\Finder;
use saso\util\Each;

final class OneUsecase implements Usecase
{
    use Output;
    private DTO $output;
    public function __construct(
        private Finder $finder,
        private Presenter $presenter,
    )
    {
    }
    public function handle(DTO $data): void
    {
        $item = $data->id->flatMap(
            fn($v)=>$this->finder->current(new FindOneById(), ['id'=>$v])
        );
        $archive = $item->flatMap(fn($v)=>$this->finder->current(new archive\FindOneByItem($v)));
        $colors = $item->flatMap(fn($v)=>$this->finder->generate(new color\FindByItem($v)))
            ->map(Each::tf(fn($v)=>fn($size)=>new Feature(
                $item->getOrElse(null),
                $v,
                $size,
            )));
        $sizes = $item->flatMap(fn($v)=>$this->finder->generate(new size\FindByItem($v)))
            ->map(fn($v)=>iterator_to_array($v));
        $quantityLogsGen = $colors->map(Each::tf(fn($v)=>Each::tf(fn($i)=>$v($i))(Each::t($sizes->getOrElse([])))))
            ->map(Each::m())
            ->map(Each::tf(fn($v)=>new QuantityLogs(
                $v,
                $this->finder->generate(new quantityLog\FindByFeature($v))
            )))
            ->map(Each::tf(function($v) {
                $v->feature->setShelf($this->finder->current(new shelf\FindOneByFeature($v->feature))->getOrElse(null));
                $v->feature->setLabelAmount($this->finder->current(new labelCache\FindOneByFeature($v->feature))->getOrElse(null));
                return $v;
            }));
        $labelSheetsAmount = $this->finder->current(new SumAll())->getOrElse(0);
        $this->output = new OneOutputData(
            $item,
            $archive,
            $quantityLogsGen,
            $labelSheetsAmount,
            $data->sheetAmount,
            $data->color,
            $data->size,
            $data->action,
        );
    }
}
