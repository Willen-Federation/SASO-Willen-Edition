<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use saso\auth\AuthInput;
use saso\auth\LoginController;
use saso\auth\LoginPresenter;
use saso\auth\LoginUsecase;
use saso\auth\LoginView;
use saso\repository\DbPrepare;
use saso\repository\Finder;
use saso\repository\Updater;
use saso\util\monad\Either;

/**
 * Verifies the failure-side redirect handling in {@see LoginUsecase}.
 *
 * The flow that prompted these tests: an embedded webview (desktop /
 * mobile) lands on `/auth/start/` without any in-flight `restoredPath`,
 * the user submits bad credentials, and the LoginUsecase has to produce
 * a redirect URL that re-renders the login form with the error banner —
 * not a path the legacy router resolves to a 404 (`/error/1/`).
 */
final class LoginUsecaseTest extends TestCase
{
    public function testFailureRedirectAnchorsOnAuthStartWhenRestoredPathIsEmpty(): void
    {
        $view  = $this->captureLoginView('', new self_FailingFinder(), $this->stubUpdater());
        self::assertSame('auth/start/error/1/', $view);
    }

    public function testFailureRedirectAppendsErrorMarkerWhenRestoredPathIsSet(): void
    {
        $view  = $this->captureLoginView('item/list/', new self_FailingFinder(), $this->stubUpdater());
        self::assertSame('item/list/error/1/', $view);
    }

    public function testFailureRedirectNormalisesTrailingSlash(): void
    {
        $view  = $this->captureLoginView('item/list', new self_FailingFinder(), $this->stubUpdater());
        self::assertSame('item/list/error/1/', $view);
    }

    private function captureLoginView(string $restoredPath, Finder $finder, Updater $updater): string
    {
        $loginCtrl = new LoginController(['id' => 'bad-user', 'password' => 'wrong-pass']);
        $data      = new AuthInput($restoredPath, isError: false, another: $loginCtrl);

        $view      = new LoginView();
        $presenter = new LoginPresenter($view);
        $usecase   = new LoginUsecase($finder, $updater, $presenter);

        $usecase->handle($data);
        $usecase->output();

        $reflection = new \ReflectionClass($view);
        $property   = $reflection->getProperty('restoredPath');
        $property->setAccessible(true);

        return (string) $property->getValue($view);
    }

    private function stubUpdater(): Updater
    {
        return new class () implements Updater {
            /** @param array<string, mixed>|null $input */
            public function exec(DbPrepare $prepare, ?array $input = []): void
            {
            }
        };
    }
}

/**
 * Finder stub that always reports "no such member" — drives the
 * LoginUsecase into the OrElse branch without touching a database.
 */
final class self_FailingFinder implements Finder
{
    /** @param array<string, mixed>|null $input */
    public function generate(DbPrepare $prepare, ?array $input = []): Either
    {
        return Either::left('no rows');
    }

    /** @param array<string, mixed>|null $input */
    public function current(DbPrepare $prepare, ?array $input = []): Either
    {
        return Either::left('no member');
    }
}
