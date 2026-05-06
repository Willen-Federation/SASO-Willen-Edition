<?php
namespace saso\member;

use saso\repository\Updater;
use saso\framework\View;
use saso\repository\member\Insert;
use saso\entity\Member;

final class AddUsecase
{
    public function __construct(
        private array $post,
        private Updater $updater,
        private AddPresenter $presenter
    ) {}
    public function exec(): View
    {
        if (!empty($this->post)) {
            $id = $this->post['id'] ?? '';
            $userName = $this->post['userName'] ?? '';
            $password = $this->post['password'] ?? '';
            if ($id && $userName && $password) {
                ($this->updater)->exec(new Insert(), [
                    'id' => $id,
                    'userName' => $userName,
                    'password' => Member::hashPassword($password)
                ]);
                header('Location: ../start/');
                exit;
            } else {
                return $this->presenter->view('All fields are required.');
            }
        }
        return $this->presenter->view('');
    }
}
