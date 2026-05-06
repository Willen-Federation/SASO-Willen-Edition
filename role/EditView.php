<?php
namespace saso\role;

use saso\entity\Role;
use saso\framework\Setter;
use saso\framework\View;

final class EditView implements View
{
    use Setter;
    private \Closure $content;
    private string $title;
    public string $error = '';
    public Role $role;

    public function display(): void { require_once 'role/template/edit.php'; }
    public function onRoot(): bool { return true; }
    public function getTitle(): string { return $this->title ?? 'ロール編集'; }
    public function getContent(): \Closure { return $this->content; }
}
