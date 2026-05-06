<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use saso\auth\AuthOutput;
use saso\auth\AuthPresenter;
use saso\auth\AuthView;
use saso\util\monad\Either;

final class AuthPresenterTest extends TestCase
{
    public function testCompleteCopiesProvidersToView(): void
    {
        $providers = [(object) ['id' => (object) ['value' => 'oidc']]];
        $view      = new AuthView();
        $presenter = new AuthPresenter($view);

        $presenter->complete(Either::of(new AuthOutput(
            restoredPath: 'item/list/',
            isError: false,
            providers: $providers,
        )));

        self::assertSame($providers, $this->readViewProperty($view, 'providers'));
    }

    /** @return mixed */
    private function readViewProperty(AuthView $view, string $property)
    {
        $reflection = new \ReflectionClass($view);
        $viewProperty = $reflection->getProperty($property);
        $viewProperty->setAccessible(true);

        return $viewProperty->getValue($view);
    }
}
