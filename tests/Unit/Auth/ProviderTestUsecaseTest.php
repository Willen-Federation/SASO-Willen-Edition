<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use saso\auth\ProviderSaveInput;
use saso\auth\ProviderTestUsecase;
use saso\util\monad\Either;

final class ProviderTestUsecaseTest extends TestCase
{
    public function testMissingUrlReturnsFailure(): void
    {
        $presenter = $this->createMock(\saso\framework\Presenter::class);
        $presenter->expects(self::once())
            ->method('complete')
            ->with(self::callback(fn (Either $e) => $e->getOrElse(null)->ok === false));

        $usecase = new ProviderTestUsecase($presenter);
        $data = new ProviderSaveInput(
            template: 'auth0',
            providerName: '',
            type: 'oidc',
            issuerUrl: '',
            clientId: '',
            clientSecret: null,
            scopes: null,
        );

        $usecase->handle($data);
        $usecase->output();
    }

    /**
     * Note: Testing real network calls (probeOidc/probeSaml) in unit tests
     * is usually avoided. Here we'd ideally mock the fetch() method,
     * but since it's private and part of the usecase, we might need
     * a separate service for fetching if we want to test it rigorously.
     *
     * For now, I'll just test the routing logic inside the usecase.
     */
}
