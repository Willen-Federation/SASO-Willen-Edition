<?php
namespace saso\role;

use saso\entity\Role;
use saso\framework\View;
use saso\repository\Finder;
use saso\repository\Updater;
use saso\repository\role\FindOne;
use saso\repository\role\Update;

final class EditUsecase
{
    public function __construct(
        private array $query,
        private array $post,
        private Finder $finder,
        private Updater $updater,
        private EditPresenter $presenter,
    ) {}

    public function exec(): View
    {
        $name = $this->query['name'] ?? $this->post['name'] ?? '';
        if ($name === '') {
            header('Location: ../start/');
            exit;
        }

        $arr = $this->fetchRole($name);
        if ($arr === []) {
            header('Location: ../start/');
            exit;
        }
        $role = $arr[0];

        if (!empty($this->post)) {
            $label = trim($this->post['label'] ?? '');
            if ($label === '') {
                return $this->presenter->view($role, '表示名は必須です。');
            }
            $perms = array_values(array_filter(
                array_keys(Role::PERMISSIONS),
                fn($k) => !empty($this->post['perm_'.$k])
            ));
            $this->updater->exec(new Update(), [
                'name'        => $name,
                'label'       => $label,
                'permissions' => json_encode($perms, JSON_UNESCAPED_UNICODE),
            ]);
            header('Location: ../start/');
            exit;
        }

        return $this->presenter->view($role, '');
    }

    private function fetchRole(string $name): array
    {
        $either = $this->finder->generate(new FindOne(), ['name' => $name]);
        $r = $either->getOrElse([]);
        return is_array($r) ? $r : iterator_to_array($r, false);
    }
}
