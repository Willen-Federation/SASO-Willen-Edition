<?php
namespace saso\role;

use saso\entity\Role;
use saso\framework\View;
use saso\repository\Updater;
use saso\repository\role\Insert;

final class AddUsecase
{
    public function __construct(
        private array $post,
        private Updater $updater,
        private AddPresenter $presenter,
    ) {}

    public function exec(): View
    {
        if (!empty($this->post)) {
            $name  = trim($this->post['name'] ?? '');
            $label = trim($this->post['label'] ?? '');
            $perms = array_filter(
                array_keys(Role::PERMISSIONS),
                fn($k) => !empty($this->post['perm_'.$k])
            );
            if ($name === '' || $label === '') {
                return $this->presenter->view('ロール名と表示名は必須です。');
            }
            if (!preg_match('/^[a-zA-Z0-9_-]{1,50}$/', $name)) {
                return $this->presenter->view('ロール名は英数字・アンダースコア・ハイフンのみ使用できます。');
            }
            $this->updater->exec(new Insert(), [
                'name'        => $name,
                'label'       => $label,
                'permissions' => json_encode(array_values($perms), JSON_UNESCAPED_UNICODE),
            ]);
            header('Location: ../start/');
            exit;
        }
        return $this->presenter->view('');
    }
}
