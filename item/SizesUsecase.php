<?php
namespace saso\item;

use saso\framework\DTO;
use saso\framework\Output;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\archive;
use saso\repository\item\FindOneById;
use saso\repository\Finder;
use saso\repository\size\FindByItem;

final class SizesUsecase implements Usecase
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
        $sizes = $item->flatMap(
            fn($v)=>$this->finder->generate(new FindByItem($v))
        );
        $this->output = new SizesOutput(
            $item,
            $sizes,
        );
    }
}
