<?php

namespace saso\auth;

use Saso\Domain\Auth\Repository\AuthProviderRepository;
use saso\framework\DTO;
use saso\framework\Output;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\util\monad\Either;

final class AuthUsecase implements Usecase
{
    use Output;

    private DTO $output;

    public function __construct(
        private AuthProviderRepository $repo,
        private Presenter $presenter,
    ) {
    }

    public function handle(DTO $data): void
    {
        $providers = $this->repo->listEnabled();
        
        $this->output = new AuthOutput(
            restoredPath: (string) $data->restoredPath,
            isError: (bool) $data->isError,
            providers: $providers,
        );
    }

    public function output(): \saso\framework\View
    {
        return $this->presenter->complete(Either::of($this->output));
    }
}
