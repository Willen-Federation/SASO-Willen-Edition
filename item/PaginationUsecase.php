<?php
namespace saso\item;

use saso\framework\DTO;
use saso\framework\Output;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\item\CountAll;
use saso\repository\Finder;

final class PaginationUsecase implements Usecase
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
        $pageAmount = (int)ceil(
            $this->finder->current(new CountAll(
            $data->search,
            $data->isArchive,
        ))->getOrElse(0) / $data->outputRow
        );
        $this->output = new PaginationOutput(
            $pageAmount,
            $data->sortby,
            $data->direction,
            empty(urlencode($data->search))?'':'/search/'.urlencode($data->search),
            $data->page,
        );
    }
}
