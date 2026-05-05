<?php
namespace saso\mypage;

use saso\common;
use saso\framework\DIContainer;
use saso\framework\Flow;
use saso\repository\DbFinder;

final class MyPageDIContainer implements DIContainer
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

        $this->ctrl = new common\EmptyController();
        $this->usecase = new MyPageUsecase(
            new DbFinder(),
            new MyPagePresenter(
                new MyPageView(),
            ),
            $memberId
        );
    }
}
