<?php
namespace saso\mypage;

use saso\framework\Setter;
use saso\framework\View;

final class MyPageErrorView implements View
{
    use Setter;

    private string $title;
    private \Closure $content;
    private string $message = 'Error';

    public function display(): void
    {
        require_once 'mypage/template/error.php';
    }

    public function onRoot(): bool
    {
        return true;
    }

    public function getTitle(): string
    {
        return $this->title ?? 'Error';
    }

    public function getContent(): \Closure
    {
        return $this->content;
    }
}
