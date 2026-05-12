<?php
namespace saso\itemAttribute;

use saso\framework\DTO;
use saso\framework\Output;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\itemAttribute\FindAll;
use saso\repository\Finder;

final class ListUsecase implements Usecase
{
    use Output;
    private DTO $output;

    public function __construct(
        private Finder $finder,
        private Presenter $presenter,
    ) {
    }

    public function handle(DTO $data): void
    {
        $this->output = new ListOutput(
            $this->finder->generate(new FindAll())
        );
    }
}
