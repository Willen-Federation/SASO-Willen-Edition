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
use saso\repository\TransactionInterface;
use saso\repository\Updater;
use saso\util\monad\Either;

final class ChangePasswordUsecase implements Usecase
{
    use OutputForSingleEntity;
    private Either $output;
    public function __construct(
        private Finder $finder,
        private Updater $updater,
        private TransactionInterface $transaction,
        private Presenter $presenter,
    )
    {
    }
    public function handle(DTO $data): void
    {
        try {
            $this->transaction->begin();

            $rawNow = $data->now->getOrElseThrow('invalid current password.');
            $newHash = $data->new->getOrElseThrow('invalid new password.');

            $this->output = $this->finder->current(new FindOneByAuth(), [
                'id'=>$_SESSION['id'],
            ])->flatMap(fn($v)=>Member::verifyPassword($rawNow, $v->password)
                ? Either::of(new Member($v->id, $v->name, $newHash))
                : Either::left('current password mismatch')
            )
            ->flatMap(fn($v)=>$this->updater->exec(new ChangePassword($v)))
            ->flatMap(fn($v)=>'changed')
            ->OrElse(fn($v)=>Either::of('errorNow'));

            $this->transaction->commit();
        } catch (\Exception $e) {
            $this->transaction->rollBack();
            $this->output = Either::left($e->getMessage());
        }
    }
}
