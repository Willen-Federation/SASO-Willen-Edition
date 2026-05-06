<?php
namespace saso\mypage;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DbFinder;
use saso\repository\DbUpdater;

final class EditProfileDIContainer implements DIContainer
{
    use Flow;

    public function isTopLevel(): bool
    {
        return false;
    }

    public function di(\Closure $inside, array $query, array $post, array $config, \DateTime $now): void
    {
        $memberId = $_SESSION['id'] ?? null;
        if (empty($memberId)) {
            $this->ctrl = new common\EmptyController();
            $this->usecase = new MyPageErrorUsecase(
                new MyPageErrorPresenter(
                    new MyPageErrorView(),
                ),
                'Not authenticated'
            );
            return;
        }

        if (empty($post)) {
            // GET: Show form
            $this->ctrl = new common\EmptyController();
            $this->usecase = new EditProfileFormUsecase(
                new DbFinder(),
                new EditProfileFormPresenter(
                    new EditProfileView(),
                ),
                $memberId
            );
        } else {
            // POST: Save changes
            $this->ctrl = new EditProfileController($post);
            $this->usecase = new EditProfileSaveUsecase(
                new DbFinder(),
                new DbUpdater(),
                new EditProfileSavePresenter(
                    new EditProfileSaveView(),
                ),
                $memberId
            );
        }
    }
}
