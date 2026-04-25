<?php
namespace saso\item;

use saso\framework\DTO;
use saso\framework\Output;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\archive;
use saso\repository\color;
use saso\repository\Finder;
use saso\repository\size;
use saso\repository\item\FindOneById;

final class AddFeatureConfirmUsecase implements Usecase
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
        )->filter(
            fn($v)=>!$this->finder->current(new archive\FindOneByItem($v))->getOrElse(null)?->archive??false
        );
        $oldColors = $item->flatMap(
            fn($v)=>$this->finder->generate(new color\FindByItem($v))
        );
        $oldSizes = $item->flatMap(
            fn($v)=>$this->finder->generate(new size\FindByItem($v))
        );
        $this->output = AddFeatureHelper::output(
            $item,
            $oldColors->flatMap(fn($v)=>iterator_to_array($v)),
            $oldSizes->flatMap(fn($v)=>iterator_to_array($v)),
            $data->colors,
            $data->sizes,
        );
    }
}