<?php
namespace saso\member;

use saso\repository\Finder;
use saso\repository\Updater;
use saso\framework\View;
use saso\repository\member\FindOneByAuth;
use saso\repository\member\Update;
use saso\repository\role\FindAll as FindAllRoles;
use Saso\Application\Auth\AdminGuard;

final class EditUsecase
{
    public function __construct(
        private array $query,
        private array $post,
        private Finder $finder,
        private Updater $updater,
        private EditPresenter $presenter,
        private AdminGuard $guard,
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

        $isAdmin = $this->guard->isAdmin($this->guard->currentMemberId());

        // Fetch all roles for the dropdown (only needed when admin)
        $roles = [];
        if ($isAdmin) {
            $rolesEither = $this->finder->generate(new FindAllRoles(), []);
            $rolesArr = $rolesEither->getOrElse([]);
            $roles = is_array($rolesArr) ? $rolesArr : iterator_to_array($rolesArr, false);
        }

        if (!empty($this->post)) {
            $userName = $this->post['userName'] ?? '';
            if ($userName) {
                $validNames = array_map(fn($r) => $r->name, $roles);
                $role = ($isAdmin && in_array($this->post['role'] ?? '', $validNames, true))
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
                return $this->presenter->view($member, 'All fields are required.', $isAdmin, $roles);
            }
        }
        return $this->presenter->view($member, '', $isAdmin, $roles);
    }
}
