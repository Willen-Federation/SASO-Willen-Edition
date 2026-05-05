<?php

namespace saso\mypage;

use saso\framework\DTO;
use saso\framework\Output;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\DbFinder;
use saso\util\monad\Either;

final class MyPageUsecase implements Usecase
{
    use Output;

    private DTO $output;

    public function __construct(
        private DbFinder $finder,
        private Presenter $presenter,
        private string $memberId,
    ) {
    }

    public function handle(DTO $data): void
    {
        $members = $this->finder->query(
            new \saso\repository\member\FindOne(),
            ['id' => $this->memberId]
        );

        if (empty($members)) {
            $this->output = new MyPageErrorOutput('Member not found');
            return;
        }

        $this->output = new MyPageOutput(
            member: $members[0],
        );
    }

    public function output(): \saso\framework\View
    {
        return $this->presenter->complete(Either::of($this->output));
    }
}
