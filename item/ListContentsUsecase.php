<?php
namespace saso\item;

use saso\framework\DTO;
use saso\framework\Output;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\item\FindAllPerPage;
use saso\repository\Finder;
use saso\util\Each;

final class ListContentsUsecase implements Usecase
{
    use Output;
    private DTO $output;
    public function __construct(
        private Finder $finder,
        private Presenter $presenter,
        private \Closure $inside,
    )
    {
    }
    public function handle(DTO $data): void
    {
        $items = $this->finder->generate(
            new FindAllPerPage(
                $data->sortby,
                $data->direction,
                $data->search,
            ), [
                'archive'=>$data->isArchive?1:0,
                'limit'=>$data->outputRow,
                'offset'=>$data->outputRow*($data->page-1),
            ]
        );
        $this->output = new ListContentsOutput(
            $items->flatMap(Each::tf(fn($i)=>
                fn($matter, $action)=>($this->inside)($matter, $action, $i)
            ))
        );
    }
}
