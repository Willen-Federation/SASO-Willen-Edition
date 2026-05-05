<?php
namespace saso\mypage;

use saso\entity\Member;
use saso\framework\Setter;
use saso\framework\View;

final class MyPageView implements View
{
    use Setter;

    private string $title;
    private \Closure $content;
    private ?Member $member = null;

    public function display(): void
    {
        require_once 'mypage/template/mypage.php';
    }

    public function onRoot(): bool
    {
        return false;
    }

    public function getTitle(): string
    {
        return $this->title ?? 'My Page';
    }

    public function getContent(): \Closure
    {
        return $this->content;
    }
}
