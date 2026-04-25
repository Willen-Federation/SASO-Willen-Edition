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
            return $data->restoredPath;
        })->OrElse(fn($v)=>Either::left($data->restoredPath.'error/1/'));
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
