<?php
namespace saso\auth;

use saso\entity\Member;
use saso\framework\DTO;
use saso\framework\OutputForSingleEntity;
use saso\framework\Presenter;
use saso\framework\Usecase;
use saso\repository\member\ChangePassword;
use saso\repository\member\FindOneByAuth;
use saso\repository\Finder;
use saso\repository\Updater;
use saso\util\monad\Either;

final class LoginUsecase implements Usecase
{
    use OutputForSingleEntity;
    private Either $output;
    public function __construct(
        private Finder $finder,
        private Updater $updater,
        private Presenter $presenter,
    )
    {
    }
    public function handle(DTO $data): void
    {
        $this->output = $data->id->flatMap(
            fn($i)=>$data->password->flatMap(
                fn($p)=>$this->finder->current(new FindOneByAuth(), [
                    'id'=>$i,
                ])->flatMap(
                    fn($member)=>Member::verifyPassword($p, $member->password)
                        ? Either::of($this->maybeRehash($member, $p))
                        : Either::left('invalid credentials')
                )
            )
        )->flatMap(function($member) use ($data){
            $_SESSION['id'] = $member->id;
            $_SESSION['time'] = time();
            $_SESSION['userName'] = $member->name;
            return self::resolveSuccessRedirect((string) $data->restoredPath);
        })->OrElse(fn($v)=>Either::left(self::buildFailureRedirect((string) $data->restoredPath)));
    }

    /**
     * Picks the post-login redirect target.
     *
     * Prefers an explicit `restoredPath` from the form (set by the legacy
     * router when the user was bounced here from a protected path). When
     * that is empty, falls back to `$_SESSION['auth.return_to']` — the slot
     * IdP-style providers (and `LocalProvider::beginLogin`) write before
     * handing off to this form. This is what makes the mobile / desktop
     * pairing flow (`/m/setup` → `/auth/start/{LOCAL_ID}` → /auth/start)
     * return to `/m/issue-pairing` after a successful local sign-in
     * instead of landing on `/` and stranding the pairing code unused.
     *
     * Same-origin paths only: a value that does not start with `/`, or any
     * `//host` / `scheme:` form, is rejected as a defence against open
     * redirects, and the session slot is cleared even when the value is
     * dropped so a stale entry cannot leak into a later login.
     */
    private static function resolveSuccessRedirect(string $restoredPath): string
    {
        if ($restoredPath !== '') {
            return $restoredPath;
        }
        $candidate = isset($_SESSION['auth.return_to']) && is_string($_SESSION['auth.return_to'])
            ? $_SESSION['auth.return_to']
            : '';
        unset($_SESSION['auth.return_to'], $_SESSION['auth.provider_id']);
        if ($candidate === '' || !str_starts_with($candidate, '/') || str_starts_with($candidate, '//')) {
            return '';
        }
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $candidate) === 1) {
            return '';
        }
        return $candidate;
    }

    /**
     * Builds the redirect target for a failed login attempt.
     *
     * When the form has no `restoredPath` (e.g. desktop / embedded webview
     * landing straight on `/auth/start/`), naively appending `error/1/`
     * produces `/error/1/`, which the legacy router resolves to a 404 page
     * instead of bouncing back to the login form. Anchoring on `auth/start/`
     * keeps the user on the login form with the error banner visible.
     */
    private static function buildFailureRedirect(string $restoredPath): string
    {
        $trimmed = trim($restoredPath, '/');
        if ($trimmed === '') {
            return 'auth/start/error/1/';
        }
        return rtrim($restoredPath, '/').'/error/1/';
    }

    /**
     * Best-effort upgrade of a legacy / non-Argon2id password hash to Argon2id.
     * Failures are swallowed so that a transient DB error does not block the login.
     */
    private function maybeRehash(Member $member, string $rawPassword): Member
    {
        if (!Member::needsRehash($member->password)) {
            return $member;
        }
        try {
            $upgraded = new Member(
                $member->id,
                $member->name,
                Member::hashPassword($rawPassword),
            );
            $this->updater->exec(new ChangePassword($upgraded));
            return $upgraded;
        } catch (\Throwable $e) {
            return $member;
        }
    }
}
