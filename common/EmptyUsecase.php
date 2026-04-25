<?php
namespace saso\common;

use saso\framework\DTO;
use saso\framework\Output;
use saso\framework\Presenter;
use saso\framework\Usecase;

final class EmptyUsecase implements Usecase
{
    use Output;
    private DTO $output;
    public function __construct(
        private Presenter $presenter,
    )
    {
    }
    public function handle(DTO $input): void
    {
        $this->output = $input;
    }
}
