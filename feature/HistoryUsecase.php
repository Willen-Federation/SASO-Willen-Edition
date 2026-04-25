<?php
namespace saso\feature;

use saso\entity\QuantityLogs;
use saso\framework\DTO;
use saso\framework\Output;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\archive\FindOneByItem;
use saso\repository\feature\FindOneByFullcode;
use saso\repository\quantityLog;
use saso\repository\Finder;

final class HistoryUsecase implements Usecase
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
        $logs = $data->id->flatMap(
            fn($i)=>$data->color->flatMap(
            fn($c)=>$data->size->flatMap(
            fn($s)=>$this->finder->current(new FindOneByFullcode($this->finder), [
                'item'=>$i,
                'color'=>$c,
                'size'=>$s,
        ]))))->flatMap(
            fn($v)=>new QuantityLogs(
                $v,
                $this->finder->generate(new quantityLog\FindByFeature($v))
            )
        );
        $archive = $logs->flatMap(
            fn($v)=>$this->finder->current(
                new FindOneByItem($v->feature->item)
            )
        );
        $this->output = new HistoryOutput(
            $logs,
            $archive,
        );
    }
}