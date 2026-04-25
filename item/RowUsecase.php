<?php
namespace saso\item;

use saso\entity;
use saso\framework\DTO;
use saso\framework\Output;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\itemVar;
use saso\repository\color;
use saso\repository\size;
use saso\repository\Finder;

final class RowUsecase implements Usecase
{
    use Output;
    private DTO $output;
    public function __construct(
        private entity\Item $item,
        private Finder $finder,
        private Presenter $presenter,
    )
    {
    }
    public function handle(DTO $data): void
    {
        $this->output = new RowOutput(
            $this->item,
            $this->finder->current(new itemVar\FindOneByItem($this->item)),
            $this->finder->generate(new color\FindByItem($this->item)),
            $this->finder->generate(new size\FindByItem($this->item)),
        );
    }
}
