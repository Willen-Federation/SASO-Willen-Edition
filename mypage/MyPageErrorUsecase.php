<?php

namespace saso\mypage;

use saso\framework\DTO;
use saso\framework\Output;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\util\monad\Either;

final class MyPageErrorUsecase implements Usecase
{
    use Output;

    private DTO $output;

    public function __construct(
        private Presenter $presenter,
        private string $error,
    ) {
    }

    public function handle(DTO $data): void
    {
        $this->output = new MyPageErrorOutput($this->error);
    }

    public function output(): \saso\framework\View
    {
        return $this->presenter->complete(Either::of($this->output));
    }
}
