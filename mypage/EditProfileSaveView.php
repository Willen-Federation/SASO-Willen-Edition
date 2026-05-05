<?php
namespace saso\mypage;

use saso\entity\Member;
use saso\framework\Setter;
use saso\framework\View;

final class EditProfileSaveView implements View
{
    use Setter;

    private string $title;
    private \Closure $content;
    private ?Member $member = null;

    public function display(): void
    {
        // Redirect to mypage after successful save
        header('Location: /mypage/start/', true, 303);
        exit;
    }

    public function onRoot(): bool
    {
        return false;
    }

    public function getTitle(): string
    {
        return $this->title ?? 'Profile Updated';
    }

    public function getContent(): \Closure
    {
        return $this->content;
    }
}
