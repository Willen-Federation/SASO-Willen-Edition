<?php
namespace saso\member;

use saso\repository\Finder;
use saso\repository\Updater;
use saso\framework\View;
use saso\repository\member\FindOneByAuth;
use saso\repository\member\Update;

final class EditUsecase
{
    public function __construct(
        private array $query,
        private array $post,
        private Finder $finder,
        private Updater $updater,
        private EditPresenter $presenter
    ) {}
    public function exec(): View
    {
        $id = $this->query['id'] ?? $this->post['id'] ?? '';
        if (!$id) {
            header('Location: ../start/');
            exit;
        }

        $membersEither = $this->finder->generate(new FindOneByAuth(), ['id' => $id]);
        $arr = is_array($r = $membersEither->getOrElse([])) ? $r : iterator_to_array($r, false);
        if (empty($arr)) {
            header('Location: ../start/');
            exit;
        }
        $member = $arr[0];

        $selfId = (string) ($_SESSION['id'] ?? '');
        $selfArr = is_array($s = $this->finder->generate(new FindOneByAuth(), ['id' => $selfId])->getOrElse([]))
            ? $s : iterator_to_array($s, false);
        $isAdmin = !empty($selfArr) && $selfArr[0]->role === 'admin';

        if (!empty($this->post)) {
            $userName = $this->post['userName'] ?? '';
            if ($userName) {
                $allowedRoles = ['admin', 'operator'];
                $role = ($isAdmin && in_array($this->post['role'] ?? '', $allowedRoles, true))
                    ? $this->post['role']
                    : $member->role;
                $this->updater->exec(new Update(), [
                    'id'       => $id,
                    'userName' => $userName,
                    'role'     => $role,
                ]);
                header('Location: ../start/');
                exit;
            } else {
                return $this->presenter->view($member, 'All fields are required.', $isAdmin);
            }
        }
        return $this->presenter->view($member, '', $isAdmin);
    }
}
