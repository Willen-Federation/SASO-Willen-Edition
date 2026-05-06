<?php
namespace saso\member;

use saso\framework\Setter;
use saso\framework\View;
use saso\entity\Member;

final class EditView implements View
{
    use Setter;
    private \Closure $content;
    private string $title;
    public string $error = '';
    public Member $member;
    public bool $isAdmin = false;
    public array $roles = [];

    public function display(): void
    {
        require_once 'member/template/edit.php';
    }
    public function onRoot(): bool
    {
        return true;
    }
    public function getTitle(): string
    {
        return $this->title ?? 'Edit Member';
    }
    public function getContent(): \Closure
    {
        return $this->content;
    }
}
