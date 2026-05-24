<?php

declare(strict_types=1);

namespace Saso\Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use saso\auth\AuthInput;
use saso\auth\LoginController;
use saso\auth\LoginPresenter;
use saso\auth\LoginUsecase;
use saso\auth\LoginView;
use saso\entity\Member;
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

    // ------------------------------------------------------------------
    // Success-path redirect resolution — covers the desktop/mobile
    // pairing flow where `/m/setup` parks the post-login target in
    // `$_SESSION['auth.return_to']`. Before the fix, an empty
    // `restoredPath` (typical of `/auth/start/` POSTs) silently dropped
    // the user on `/` and stranded the pairing code unused at
    // `/m/issue-pairing`.
    // ------------------------------------------------------------------

    public function testSuccessReturnsRestoredPathWhenNonEmpty(): void
    {
        $_SESSION['auth.return_to'] = '/m/issue-pairing';
        $view = $this->captureSuccessView(
            restoredPath: '/item/list/',
            loginId:      'validuser',
            password:     'validpass',
        );
        // Explicit form-supplied target wins over the session slot — the
        // session slot is reserved for the case where the form had
        // nothing to say.
        self::assertSame('/item/list/', $view);
    }

    public function testSuccessFallsBackToSessionReturnToWhenRestoredPathEmpty(): void
    {
        $_SESSION['auth.return_to'] = '/m/issue-pairing';
        $view = $this->captureSuccessView(
            restoredPath: '',
            loginId:      'validuser',
            password:     'validpass',
        );
        self::assertSame('/m/issue-pairing', $view);
        // Slot is consumed on use so a later sign-in cannot replay it.
        self::assertArrayNotHasKey('auth.return_to', $_SESSION);
    }

    public function testSuccessReturnsEmptyWhenNoFallbackAvailable(): void
    {
        unset($_SESSION['auth.return_to']);
        $view = $this->captureSuccessView(
            restoredPath: '',
            loginId:      'validuser',
            password:     'validpass',
        );
        // Web flow with no in-flight protected path — let the existing
        // `Redirect::redirect('')` resolve to the program root.
        self::assertSame('', $view);
    }

    public function testSuccessRejectsProtocolRelativeSessionReturnTo(): void
    {
        // `//attacker.example` would resolve as a same-scheme,
        // different-host URL — a textbook open-redirect vector. The
        // slot is cleared even though the value is dropped, so a stale
        // hostile entry cannot survive into a benign next login.
        $_SESSION['auth.return_to'] = '//attacker.example/m/issue-pairing';
        $view = $this->captureSuccessView(
            restoredPath: '',
            loginId:      'validuser',
            password:     'validpass',
        );
        self::assertSame('', $view);
        self::assertArrayNotHasKey('auth.return_to', $_SESSION);
    }

    public function testSuccessRejectsAbsoluteUrlSessionReturnTo(): void
    {
        $_SESSION['auth.return_to'] = 'https://attacker.example/m/issue-pairing';
        $view = $this->captureSuccessView(
            restoredPath: '',
            loginId:      'validuser',
            password:     'validpass',
        );
        self::assertSame('', $view);
        self::assertArrayNotHasKey('auth.return_to', $_SESSION);
    }

    public function testSuccessRejectsNonAbsolutePathSessionReturnTo(): void
    {
        // Relative paths (`m/issue-pairing` without the leading `/`)
        // would be resolved by the browser against the current URL,
        // which is `/auth/start/` — landing the user on
        // `/auth/m/issue-pairing` and 404ing the legacy router. Treat
        // these the same as an unsafe value: reject and clear.
        $_SESSION['auth.return_to'] = 'm/issue-pairing';
        $view = $this->captureSuccessView(
            restoredPath: '',
            loginId:      'validuser',
            password:     'validpass',
        );
        self::assertSame('', $view);
        self::assertArrayNotHasKey('auth.return_to', $_SESSION);
    }

    protected function setUp(): void
    {
        // Ensure each test starts from a clean session-key surface. The
        // monad / DTO layer reads `$_SESSION` directly, so leakage
        // between tests would otherwise produce order-dependent passes.
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    private function captureSuccessView(
        string $restoredPath,
        string $loginId,
        string $password,
    ): string {
        $member  = new Member($loginId, 'Test User', Member::hashPassword($password));
        $finder  = new self_SuccessFinder($member);
        $updater = $this->stubUpdater();
        return $this->captureLoginView(
            restoredPath: $restoredPath,
            finder:       $finder,
            updater:      $updater,
            loginId:      $loginId,
            password:     $password,
        );
    }

    private function captureLoginView(
        string $restoredPath,
        Finder $finder,
        Updater $updater,
        string $loginId = 'bad-user',
        string $password = 'wrong-pass',
    ): string {
        $loginCtrl = new LoginController(['id' => $loginId, 'password' => $password]);
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

/**
 * Finder stub that returns a fixed {@see Member} record so the
 * LoginUsecase reaches its success branch without hitting a real DB.
 * Pairs with {@see Member::hashPassword} so the password verification
 * step inside the usecase passes for the matching raw password.
 */
final class self_SuccessFinder implements Finder
{
    public function __construct(private Member $member)
    {
    }

    /** @param array<string, mixed>|null $input */
    public function generate(DbPrepare $prepare, ?array $input = []): Either
    {
        return Either::of($this->member);
    }

    /** @param array<string, mixed>|null $input */
    public function current(DbPrepare $prepare, ?array $input = []): Either
    {
        return Either::of($this->member);
    }
}
