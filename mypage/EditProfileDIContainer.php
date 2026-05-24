<?php
namespace saso\mypage;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DbFinder;
use saso\repository\DbUpdater;
use saso\util\CSRFtoken;

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
            return;
        }

        // POST: Save changes — reject when the CSRF token is missing or
        // does not match the one bound to the current session. Without
        // this guard a malicious cross-site form could update the logged-in
        // member's profile (displayName, bio, avatarUrl) silently.
        if (!CSRFtoken::verify((string) ($post['csrftoken'] ?? ''))) {
            $this->ctrl = new common\EmptyController();
            $this->usecase = new MyPageErrorUsecase(
                new MyPageErrorPresenter(
                    new MyPageErrorView(),
                ),
                'Invalid request'
            );
            return;
        }

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
