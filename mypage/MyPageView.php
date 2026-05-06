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
    private array $authMethods = [];
    private array $availableProviders = [];
    private array $passkeys = [];

    public function display(): void
    {
        require_once 'mypage/template/mypage.php';
    }

    public function onRoot(): bool
    {
        return true;
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
